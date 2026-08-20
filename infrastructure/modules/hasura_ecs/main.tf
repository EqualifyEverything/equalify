locals {
  name = "${var.project_name}-${var.environment}-hasura"
}

resource "aws_ecs_cluster" "this" {
  name = local.name

  setting {
    name  = "containerInsights"
    value = "enabled"
  }
}

resource "aws_cloudwatch_log_group" "this" {
  name              = "/ecs/${local.name}"
  retention_in_days = var.log_retention_in_days
}

data "aws_iam_policy_document" "ecs_assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["ecs-tasks.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "execution" {
  name               = "${local.name}-execution"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_role.json
}

resource "aws_iam_role_policy_attachment" "execution" {
  role       = aws_iam_role.execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role" "task" {
  name               = "${local.name}-task"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_role.json
}

resource "aws_ecs_task_definition" "this" {
  family                   = local.name
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = var.cpu
  memory                   = var.memory
  execution_role_arn       = aws_iam_role.execution.arn
  task_role_arn            = aws_iam_role.task.arn

  container_definitions = jsonencode([
    {
      name      = "hasura"
      image     = var.hasura_image
      essential = true

      portMappings = [{
        containerPort = 8080
        protocol      = "tcp"
      }]

      environment = [
        { name = "HASURA_GRAPHQL_DATABASE_URL", value = var.database_url },
        { name = "HASURA_GRAPHQL_ADMIN_SECRET", value = var.admin_secret },
        { name = "HASURA_GRAPHQL_ENABLE_CONSOLE", value = "true" },
        { name = "HASURA_GRAPHQL_CORS_DOMAIN", value = var.cors_allowed_origins },
        { name = "HASURA_GRAPHQL_ENABLED_LOG_TYPES", value = "startup, http-log, webhook-log, websocket-log, query-log" },
        # Without this, Hasura has no way to validate the Cognito ID token in
        # the Authorization header (the x-hasura-* claims routes/cognito's
        # tokenGeneration.ts injects at the standard "https://hasura.io/jwt/claims"
        # location are meaningless to Hasura until it's told to look for and
        # verify them) — every request falls back to requiring the raw admin
        # secret instead.
        { name = "HASURA_GRAPHQL_JWT_SECRET", value = jsonencode({
          type     = "RS256"
          jwk_url  = "https://cognito-idp.${var.aws_region}.amazonaws.com/${var.user_pool_id}/.well-known/jwks.json"
          issuer   = "https://cognito-idp.${var.aws_region}.amazonaws.com/${var.user_pool_id}"
          audience = var.web_client_id
          # Cognito's PreTokenGeneration trigger can only set claim override
          # values as strings (a hard AWS constraint) — tokenGeneration.ts
          # JSON.stringifies the whole x-hasura-* claims object to work
          # around it, so Hasura needs to know to parse it back out rather
          # than expect an already-decoded JSON object at that claims key.
          claims_format = "stringified_json"
        }) },
        # With JWT auth configured above, Hasura requires either a valid
        # token or this fallback for every request — otherwise a request
        # with no Authorization header at all (e.g. Logo.tsx's deliberately
        # public cobranding fetch, meant to work logged-out) is rejected
        # outright instead of falling back to the "anonymous" role the
        # metadata already defines permissions for.
        { name = "HASURA_GRAPHQL_UNAUTHORIZED_ROLE", value = "anonymous" },
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.this.name
          "awslogs-region"        = data.aws_region.current.name
          "awslogs-stream-prefix" = "hasura"
        }
      }
    }
  ])
}

data "aws_region" "current" {}

resource "aws_lb" "this" {
  name               = local.name
  internal           = false
  load_balancer_type = "application"
  security_groups    = [var.alb_security_group_id]
  subnets            = var.public_subnet_ids
}

resource "aws_lb_target_group" "this" {
  name        = local.name
  port        = 8080
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"

  health_check {
    path                = "/healthz"
    healthy_threshold   = 2
    unhealthy_threshold = 5
    interval            = 30
    timeout             = 10
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.this.arn
  port              = 80
  protocol          = "HTTP"

  dynamic "default_action" {
    for_each = var.acm_certificate_arn != null ? [1] : []
    content {
      type = "redirect"
      redirect {
        port        = "443"
        protocol    = "HTTPS"
        status_code = "HTTP_301"
      }
    }
  }

  dynamic "default_action" {
    for_each = var.acm_certificate_arn == null ? [1] : []
    content {
      type             = "forward"
      target_group_arn = aws_lb_target_group.this.arn
    }
  }
}

resource "aws_lb_listener" "https" {
  count = var.acm_certificate_arn != null ? 1 : 0

  load_balancer_arn = aws_lb.this.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"
  certificate_arn   = var.acm_certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.this.arn
  }
}

# When no custom domain is configured, the ALB only gets an HTTP (port 80)
# listener above — there's no way to get a browser-trusted HTTPS certificate
# for its default *.elb.amazonaws.com hostname without owning that domain.
# A frontend served over HTTPS (CloudFront) then can't call it at all:
# browsers silently block the request as mixed content. Front it with
# CloudFront instead, the same trick modules/frontend_hosting uses for its
# S3 bucket — CloudFront's own *.cloudfront.net domain gets automatic
# trusted HTTPS with zero custom-domain setup, and passes both the HTTP
# GraphQL endpoint and WebSocket subscriptions through to the origin
# unchanged. Only needed here in the no-custom-domain path; with a custom
# domain the ALB already terminates HTTPS directly via the listener above.
resource "aws_cloudfront_distribution" "this" {
  count = var.domain_name == null ? 1 : 0

  enabled = true
  comment = "${local.name}: HTTPS front for the HTTP-only ALB (no custom domain configured)"

  origin {
    domain_name = aws_lb.this.dns_name
    origin_id   = "hasura-alb"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "http-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  default_cache_behavior {
    allowed_methods        = ["GET", "HEAD", "OPTIONS", "PUT", "POST", "PATCH", "DELETE"]
    cached_methods         = ["GET", "HEAD"]
    target_origin_id       = "hasura-alb"
    viewer_protocol_policy = "https-only"
    compress               = true

    forwarded_values {
      query_string = true
      headers      = ["Authorization", "Content-Type", "X-Hasura-Admin-Secret", "X-Hasura-Role"]
      cookies {
        forward = "none"
      }
    }

    # GraphQL responses are per-request/per-user — never cache.
    min_ttl     = 0
    default_ttl = 0
    max_ttl     = 0
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = true
  }
}

resource "aws_ecs_service" "this" {
  name            = local.name
  cluster         = aws_ecs_cluster.this.id
  task_definition = aws_ecs_task_definition.this.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"

  # Without this, `terraform apply` returns as soon as ECS accepts the new
  # task definition — not once the new task is actually healthy and serving
  # traffic. Every Hasura config change (JWT secret, unauthorized role, ...)
  # otherwise looks "applied" while the old task is still what's live behind
  # the ALB for another minute or two.
  wait_for_steady_state = true

  network_configuration {
    subnets          = var.private_subnet_ids
    security_groups  = [var.task_security_group_id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.this.arn
    container_name   = "hasura"
    container_port   = 8080
  }

  depends_on = [aws_lb_listener.http]
}
