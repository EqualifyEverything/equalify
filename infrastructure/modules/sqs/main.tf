locals {
  name = "${var.project_name}-${var.environment}"
}

# Queue names are prefixed with project/environment (unlike the legacy fixed
# names `scanHtml.fifo`/`scanPdf.fifo`) so a single AWS account can host more
# than one environment side by side. The application never hardcodes a queue
# name — it only reads SQS_HTML_QUEUE_URL / SQS_PDF_QUEUE_URL from env vars,
# which Terraform sets to whatever URL is actually created here.

resource "aws_sqs_queue" "scan_html_dlq" {
  name                      = "${local.name}-scanHtml-dlq.fifo"
  fifo_queue                = true
  message_retention_seconds = 1209600 # 14 days
}

resource "aws_sqs_queue" "scan_html" {
  name                        = "${local.name}-scanHtml.fifo"
  fifo_queue                  = true
  content_based_deduplication = true
  visibility_timeout_seconds  = var.visibility_timeout_seconds

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.scan_html_dlq.arn
    maxReceiveCount     = var.max_receive_count
  })
}

resource "aws_sqs_queue" "scan_pdf_dlq" {
  name                      = "${local.name}-scanPdf-dlq.fifo"
  fifo_queue                = true
  message_retention_seconds = 1209600
}

resource "aws_sqs_queue" "scan_pdf" {
  name                        = "${local.name}-scanPdf.fifo"
  fifo_queue                  = true
  content_based_deduplication = true
  visibility_timeout_seconds  = var.visibility_timeout_seconds

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.scan_pdf_dlq.arn
    maxReceiveCount     = var.max_receive_count
  })
}
