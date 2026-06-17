locals {
  frontend_domain = var.domain_name != null ? "app.${var.domain_name}" : null
  api_domain      = var.domain_name != null ? "api.${var.domain_name}" : null
  graphql_domain  = var.domain_name != null ? "graphql.${var.domain_name}" : null

  use_custom_domains = var.domain_name != null && var.route53_zone_id != null

  artifacts_dir = "${path.module}/artifacts"
}

# =============================================================================
# Networking
# =============================================================================

module "networking" {
  source = "./modules/networking"

  project_name       = var.project_name
  environment        = var.environment
  vpc_cidr           = var.vpc_cidr
  single_nat_gateway = var.single_nat_gateway
}

# =============================================================================
# Secrets
# =============================================================================

module "secrets" {
  source = "./modules/secrets"

  project_name = var.project_name
  environment  = var.environment
  sso_enabled  = var.sso_enabled
}

data "aws_secretsmanager_secret_version" "sso_config" {
  count      = var.sso_enabled ? 1 : 0
  secret_id  = module.secrets.sso_config_secret_arn
  depends_on = [module.secrets]
}

locals {
  sso_config = var.sso_enabled ? jsondecode(data.aws_secretsmanager_secret_version.sso_config[0].secret_string) : null
}

# =============================================================================
# RDS
# =============================================================================

module "rds" {
  source = "./modules/rds"

  project_name       = var.project_name
  environment        = var.environment
  vpc_id             = module.networking.vpc_id
  private_subnet_ids = module.networking.private_subnet_ids
  security_group_id  = module.networking.rds_security_group_id

  db_name     = var.db_name
  db_username = var.db_username
  db_password = module.secrets.db_password

  instance_class      = var.db_instance_class
  allocated_storage   = var.db_allocated_storage
  multi_az            = var.db_multi_az
  deletion_protection = var.db_deletion_protection
  skip_final_snapshot = var.db_skip_final_snapshot
}

# =============================================================================
# Cognito
# =============================================================================

module "cognito" {
  source = "./modules/cognito"

  project_name          = var.project_name
  environment           = var.environment
  cognito_domain_prefix = var.cognito_domain_prefix

  callback_urls = [local.use_custom_domains ? "https://${local.frontend_domain}" : "https://${module.frontend_hosting.distribution_domain_name}"]
  logout_urls   = [local.use_custom_domains ? "https://${local.frontend_domain}" : "https://${module.frontend_hosting.distribution_domain_name}"]
}

# =============================================================================
# SQS
# =============================================================================

module "sqs" {
  source = "./modules/sqs"

  project_name = var.project_name
  environment  = var.environment
}

# =============================================================================
# Custom domain certs + DNS (only when domain_name + route53_zone_id are set)
# =============================================================================

resource "aws_acm_certificate" "frontend" {
  count = local.use_custom_domains ? 1 : 0

  provider          = aws.us_east_1
  domain_name       = local.frontend_domain
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "frontend_cert_validation" {
  for_each = local.use_custom_domains ? {
    for dvo in aws_acm_certificate.frontend[0].domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      type   = dvo.resource_record_type
      record = dvo.resource_record_value
    }
  } : {}

  zone_id         = var.route53_zone_id
  name            = each.value.name
  type            = each.value.type
  records         = [each.value.record]
  ttl             = 60
  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "frontend" {
  count = local.use_custom_domains ? 1 : 0

  provider                = aws.us_east_1
  certificate_arn         = aws_acm_certificate.frontend[0].arn
  validation_record_fqdns = [for r in aws_route53_record.frontend_cert_validation : r.fqdn]
}

resource "aws_acm_certificate" "api" {
  count = local.use_custom_domains ? 1 : 0

  domain_name       = local.api_domain
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "api_cert_validation" {
  for_each = local.use_custom_domains ? {
    for dvo in aws_acm_certificate.api[0].domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      type   = dvo.resource_record_type
      record = dvo.resource_record_value
    }
  } : {}

  zone_id         = var.route53_zone_id
  name            = each.value.name
  type            = each.value.type
  records         = [each.value.record]
  ttl             = 60
  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "api" {
  count = local.use_custom_domains ? 1 : 0

  certificate_arn         = aws_acm_certificate.api[0].arn
  validation_record_fqdns = [for r in aws_route53_record.api_cert_validation : r.fqdn]
}

resource "aws_acm_certificate" "graphql" {
  count = local.use_custom_domains ? 1 : 0

  domain_name       = local.graphql_domain
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_route53_record" "graphql_cert_validation" {
  for_each = local.use_custom_domains ? {
    for dvo in aws_acm_certificate.graphql[0].domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      type   = dvo.resource_record_type
      record = dvo.resource_record_value
    }
  } : {}

  zone_id         = var.route53_zone_id
  name            = each.value.name
  type            = each.value.type
  records         = [each.value.record]
  ttl             = 60
  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "graphql" {
  count = local.use_custom_domains ? 1 : 0

  certificate_arn         = aws_acm_certificate.graphql[0].arn
  validation_record_fqdns = [for r in aws_route53_record.graphql_cert_validation : r.fqdn]
}

# =============================================================================
# Lambda functions
# =============================================================================

module "lambda_scan_sqs_router" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-scan-sqs-router"
  description   = "Routes scan requests from the backend into the scanHtml/scanPdf FIFO queues."
  runtime       = "nodejs22.x"
  handler       = "lambda.handler"
  artifact_path = "${local.artifacts_dir}/node-stub.zip"

  memory_size = 256
  timeout     = 30

  environment_variables = {
    AWS_REGION                   = var.aws_region
    SQS_HTML_QUEUE_URL           = module.sqs.scan_html_queue_url
    SQS_PDF_QUEUE_URL            = module.sqs.scan_pdf_queue_url
    POWERTOOLS_METRICS_NAMESPACE = "${var.project_name}${var.environment}"
  }

  additional_policy_statements = [
    {
      sid       = "SendToScanQueues"
      actions   = ["sqs:SendMessage"]
      resources = [module.sqs.scan_html_queue_arn, module.sqs.scan_pdf_queue_arn]
    }
  ]
}

module "lambda_scan_html" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-scan-html"
  description   = "Scans an HTML page with headless Chromium + axe-core."
  runtime       = "nodejs22.x"
  handler       = "lambda.handler"
  artifact_path = "${local.artifacts_dir}/node-stub.zip"

  memory_size = var.scan_lambda_memory_size
  timeout     = var.scan_lambda_timeout

  environment_variables = {
    SCAN_WEBHOOK_URL             = local.use_custom_domains ? "https://${local.api_domain}/public/scanWebhook" : "${module.api_gateway.api_endpoint}/public/scanWebhook"
    POWERTOOLS_METRICS_NAMESPACE = "${var.project_name}${var.environment}"
  }

  sqs_event_source = {
    queue_arn  = module.sqs.scan_html_queue_arn
    batch_size = 5
  }
}

module "lambda_verapdf_interface" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-verapdf-interface"
  description   = "Validates PDFs against veraPDF (PDF/UA) rules. Invoked synchronously by scan-pdf."
  runtime       = "java17"
  handler       = "com.equalifyuic.app.handler"
  artifact_path = "${local.artifacts_dir}/java-stub.jar"

  memory_size = 1024
  timeout     = 60
}

module "lambda_scan_pdf" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-scan-pdf"
  description   = "Scans a PDF: HTML accessibility checks + veraPDF validation via verapdf-interface."
  runtime       = "nodejs22.x"
  handler       = "lambda.handler"
  artifact_path = "${local.artifacts_dir}/node-stub.zip"

  memory_size = var.scan_lambda_memory_size
  timeout     = var.scan_lambda_timeout

  environment_variables = {
    SCAN_WEBHOOK_URL             = local.use_custom_domains ? "https://${local.api_domain}/public/scanWebhook" : "${module.api_gateway.api_endpoint}/public/scanWebhook"
    VERAPDF_FUNCTION_NAME        = module.lambda_verapdf_interface.function_name
    AWS_REGION                   = var.aws_region
    POWERTOOLS_METRICS_NAMESPACE = "${var.project_name}${var.environment}"
  }

  sqs_event_source = {
    queue_arn  = module.sqs.scan_pdf_queue_arn
    batch_size = 5
  }

  additional_policy_statements = [
    {
      sid       = "InvokeVerapdfInterface"
      actions   = ["lambda:InvokeFunction"]
      resources = [module.lambda_verapdf_interface.function_arn]
    }
  ]
}

module "lambda_crawler" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-crawler"
  description   = "Sitemap discovery for a given URL, called directly from the frontend via a Function URL."
  runtime       = "nodejs22.x"
  handler       = "lambda.handler"
  artifact_path = "${local.artifacts_dir}/node-stub.zip"

  memory_size = 256
  timeout     = 30

  create_function_url = true
  function_url_cors = {
    allow_origins = local.use_custom_domains ? ["https://${local.frontend_domain}"] : ["*"]
    allow_methods = ["POST"]
    allow_headers = ["content-type"]
  }
}

module "lambda_backend" {
  source = "./modules/lambda_function"

  function_name = "${var.project_name}-${var.environment}-api"
  description   = "Equalify backend API (apps/backend) — auth, scans orchestration, GraphQL passthrough."
  runtime       = "nodejs22.x"
  handler       = "index.handler"
  artifact_path = "${local.artifacts_dir}/node-stub.zip"

  memory_size = var.backend_lambda_memory_size
  timeout     = var.backend_lambda_timeout

  vpc_config = {
    subnet_ids         = module.networking.private_subnet_ids
    security_group_ids = [module.networking.backend_lambda_security_group_id]
  }

  environment_variables = merge(
    {
      DB_USER     = var.db_username
      DB_HOST     = module.rds.address
      DB_NAME     = module.rds.db_name
      DB_PASSWORD = module.secrets.db_password

      GRAPHQL_URL = local.use_custom_domains ? "https://${local.graphql_domain}/v1/graphql" : module.hasura_ecs.graphql_url

      USER_POOL_ID  = module.cognito.user_pool_id
      WEB_CLIENT_ID = module.cognito.web_client_id

      SSO_ENABLED = var.sso_enabled ? "1" : "0"

      AWS_REGION      = var.aws_region
      SES_ADMIN_EMAIL = var.ses_admin_email

      SQS_ROUTER_FUNCTION_NAME = module.lambda_scan_sqs_router.function_name

      POWERTOOLS_METRICS_NAMESPACE = "${var.project_name}${var.environment}"

      APP_URL        = var.app_url != null ? var.app_url : (local.use_custom_domains ? "https://${local.frontend_domain}" : "https://${module.frontend_hosting.distribution_domain_name}")
      BRAND_URL      = var.brand_url
      BRAND_LOGO_URL = var.brand_logo_url

      WEBHOOKSECRET = module.secrets.webhook_secret

      STAGING = var.environment == "staging" ? "1" : "0"
    },
    var.sso_enabled ? {
      SSO_JWKS          = local.sso_config.SSO_JWKS
      SSO_CLIENT_ID     = local.sso_config.SSO_CLIENT_ID
      SSO_TENANT        = local.sso_config.SSO_TENANT
      SSO_EMAIL_DOMAINS = local.sso_config.SSO_EMAIL_DOMAINS
    } : {}
  )

  additional_policy_statements = [
    {
      sid       = "BedrockInvoke"
      actions   = ["bedrock:InvokeModel", "bedrock:ListFoundationModels"]
      resources = ["*"]
    },
    {
      sid       = "SesSendEmail"
      actions   = ["ses:SendEmail", "ses:SendRawEmail"]
      resources = ["*"]
    },
    {
      sid = "CognitoAdmin"
      actions = [
        "cognito-idp:AdminCreateUser",
        "cognito-idp:AdminSetUserPassword",
        "cognito-idp:AdminGetUser",
        "cognito-idp:AdminUpdateUserAttributes",
        "cognito-idp:AdminDeleteUser",
        "cognito-idp:AdminInitiateAuth",
        "cognito-idp:ListUsers",
      ]
      resources = [module.cognito.user_pool_arn]
    },
    {
      sid       = "InvokeScanSqsRouter"
      actions   = ["lambda:InvokeFunction"]
      resources = [module.lambda_scan_sqs_router.function_arn]
    },
    {
      sid       = "PutMetrics"
      actions   = ["cloudwatch:PutMetricData"]
      resources = ["*"]
    },
  ]
}

# =============================================================================
# API Gateway (HTTP API) — fronts the backend Lambda
# =============================================================================

module "api_gateway" {
  source = "./modules/api_gateway"

  project_name = var.project_name
  environment  = var.environment

  backend_lambda_function_name = module.lambda_backend.function_name
  backend_lambda_invoke_arn    = module.lambda_backend.invoke_arn

  domain_name         = local.use_custom_domains ? local.api_domain : null
  acm_certificate_arn = local.use_custom_domains ? aws_acm_certificate_validation.api[0].certificate_arn : null
}

resource "aws_route53_record" "api" {
  count = local.use_custom_domains ? 1 : 0

  zone_id = var.route53_zone_id
  name    = local.api_domain
  type    = "A"

  alias {
    name                   = module.api_gateway.custom_domain_target
    zone_id                = module.api_gateway.custom_domain_hosted_zone_id
    evaluate_target_health = false
  }
}

# =============================================================================
# Hasura (ECS Fargate)
# =============================================================================

module "hasura_ecs" {
  source = "./modules/hasura_ecs"

  project_name = var.project_name
  environment  = var.environment

  vpc_id                 = module.networking.vpc_id
  public_subnet_ids      = module.networking.public_subnet_ids
  private_subnet_ids     = module.networking.private_subnet_ids
  alb_security_group_id  = module.networking.alb_security_group_id
  task_security_group_id = module.networking.hasura_task_security_group_id

  hasura_image = var.hasura_image
  database_url = "postgresql://${var.db_username}:${module.secrets.db_password}@${module.rds.address}:${module.rds.port}/${module.rds.db_name}"
  admin_secret = module.secrets.hasura_admin_secret

  cpu           = var.hasura_cpu
  memory        = var.hasura_memory
  desired_count = var.hasura_desired_count

  domain_name         = local.use_custom_domains ? local.graphql_domain : null
  acm_certificate_arn = local.use_custom_domains ? aws_acm_certificate_validation.graphql[0].certificate_arn : null

  cors_allowed_origins = local.use_custom_domains ? "https://${local.frontend_domain}" : "*"
}

resource "aws_route53_record" "graphql" {
  count = local.use_custom_domains ? 1 : 0

  zone_id = var.route53_zone_id
  name    = local.graphql_domain
  type    = "A"

  alias {
    name                   = module.hasura_ecs.alb_dns_name
    zone_id                = module.hasura_ecs.alb_zone_id
    evaluate_target_health = true
  }
}

# =============================================================================
# Frontend hosting (S3 + CloudFront)
# =============================================================================

module "frontend_hosting" {
  source = "./modules/frontend_hosting"

  project_name = var.project_name
  environment  = var.environment

  domain_name         = local.use_custom_domains ? local.frontend_domain : null
  acm_certificate_arn = local.use_custom_domains ? aws_acm_certificate_validation.frontend[0].certificate_arn : null
}

resource "aws_route53_record" "frontend" {
  count = local.use_custom_domains ? 1 : 0

  zone_id = var.route53_zone_id
  name    = local.frontend_domain
  type    = "A"

  alias {
    name                   = module.frontend_hosting.distribution_domain_name
    zone_id                = "Z2FDTNDATAQYW2" # CloudFront's fixed global hosted zone ID
    evaluate_target_health = false
  }
}

# =============================================================================
# Monitoring
# =============================================================================

module "monitoring" {
  source = "./modules/monitoring"

  project_name = var.project_name
  environment  = var.environment
  alarm_email  = var.alarm_email

  lambda_function_names = [
    module.lambda_scan_sqs_router.function_name,
    module.lambda_scan_html.function_name,
    module.lambda_scan_pdf.function_name,
    module.lambda_verapdf_interface.function_name,
    module.lambda_crawler.function_name,
    module.lambda_backend.function_name,
  ]

  rds_instance_id = module.rds.db_instance_id

  alb_arn_suffix          = module.hasura_ecs.alb_arn_suffix
  target_group_arn_suffix = module.hasura_ecs.target_group_arn_suffix
  ecs_cluster_name        = module.hasura_ecs.cluster_name
  ecs_service_name        = module.hasura_ecs.service_name
}
