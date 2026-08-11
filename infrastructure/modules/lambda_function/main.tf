data "aws_partition" "current" {}

data "aws_iam_policy_document" "assume_role" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["lambda.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "this" {
  name               = "${var.function_name}-role"
  assume_role_policy = data.aws_iam_policy_document.assume_role.json
}

resource "aws_cloudwatch_log_group" "this" {
  name              = "/aws/lambda/${var.function_name}"
  retention_in_days = var.log_retention_in_days
}

# Logs-only baseline, scoped to this function's own log group rather than
# the AWS-managed AWSLambdaBasicExecutionRole's `Resource: "*"`.
data "aws_iam_policy_document" "logs" {
  statement {
    sid       = "CreateLogGroup"
    effect    = "Allow"
    actions   = ["logs:CreateLogGroup"]
    resources = ["arn:${data.aws_partition.current.partition}:logs:*:*:*"]
  }

  statement {
    sid       = "WriteLogs"
    effect    = "Allow"
    actions   = ["logs:CreateLogStream", "logs:PutLogEvents"]
    resources = ["${aws_cloudwatch_log_group.this.arn}:*"]
  }
}

resource "aws_iam_role_policy" "logs" {
  name   = "logs"
  role   = aws_iam_role.this.id
  policy = data.aws_iam_policy_document.logs.json
}

resource "aws_iam_role_policy" "vpc" {
  count = var.vpc_config != null ? 1 : 0

  name = "vpc-access"
  role = aws_iam_role.this.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "ec2:CreateNetworkInterface",
        "ec2:DescribeNetworkInterfaces",
        "ec2:DeleteNetworkInterface",
        "ec2:AssignPrivateIpAddresses",
        "ec2:UnassignPrivateIpAddresses",
      ]
      Resource = "*"
    }]
  })
}

data "aws_iam_policy_document" "additional" {
  count = length(var.additional_policy_statements) > 0 ? 1 : 0

  dynamic "statement" {
    for_each = var.additional_policy_statements
    content {
      sid       = statement.value.sid
      effect    = statement.value.effect
      actions   = statement.value.actions
      resources = statement.value.resources
    }
  }
}

resource "aws_iam_role_policy" "additional" {
  count = length(var.additional_policy_statements) > 0 ? 1 : 0

  name   = "additional-permissions"
  role   = aws_iam_role.this.id
  policy = data.aws_iam_policy_document.additional[0].json
}

resource "aws_lambda_function" "this" {
  function_name = var.function_name
  description   = var.description
  role          = aws_iam_role.this.arn

  filename         = var.artifact_path
  source_code_hash = filebase64sha256(var.artifact_path)

  runtime       = var.runtime
  handler       = var.handler
  architectures = var.architectures
  memory_size   = var.memory_size
  timeout       = var.timeout

  reserved_concurrent_executions = var.reserved_concurrent_executions
  layers                         = var.layers

  environment {
    variables = var.environment_variables
  }

  dynamic "vpc_config" {
    for_each = var.vpc_config != null ? [var.vpc_config] : []
    content {
      subnet_ids         = vpc_config.value.subnet_ids
      security_group_ids = vpc_config.value.security_group_ids
    }
  }

  depends_on = [
    aws_cloudwatch_log_group.this,
    aws_iam_role_policy.logs,
  ]

  lifecycle {
    # Real code is pushed by GitHub Actions (`aws lambda update-function-code`)
    # after this initial apply — don't let `terraform apply` clobber it.
    # layers is ignored for the same reason: scripts/deploy-app.sh attaches
    # the chromium layer for scan-html out-of-band the same way.
    ignore_changes = [filename, source_code_hash, layers]
  }
}

resource "aws_lambda_event_source_mapping" "sqs" {
  count = var.sqs_event_source != null ? 1 : 0

  event_source_arn = var.sqs_event_source.queue_arn
  function_name    = aws_lambda_function.this.arn

  batch_size                         = var.sqs_event_source.batch_size
  maximum_batching_window_in_seconds = var.sqs_event_source.maximum_batching_window_in_seconds

  function_response_types = ["ReportBatchItemFailures"]
}

resource "aws_iam_role_policy" "sqs_consume" {
  count = var.sqs_event_source != null ? 1 : 0

  name = "sqs-consume"
  role = aws_iam_role.this.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "sqs:ReceiveMessage",
        "sqs:DeleteMessage",
        "sqs:GetQueueAttributes",
      ]
      Resource = var.sqs_event_source.queue_arn
    }]
  })
}

resource "aws_lambda_function_url" "this" {
  count = var.create_function_url ? 1 : 0

  function_name      = aws_lambda_function.this.function_name
  authorization_type = "NONE"

  dynamic "cors" {
    for_each = var.function_url_cors != null ? [var.function_url_cors] : []
    content {
      allow_origins = cors.value.allow_origins
      allow_methods = cors.value.allow_methods
      allow_headers = cors.value.allow_headers
    }
  }
}
