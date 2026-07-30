variable "function_name" {
  type = string
}

variable "description" {
  type    = string
  default = ""
}

variable "runtime" {
  description = "Lambda runtime identifier, e.g. nodejs22.x or java17."
  type        = string
}

variable "handler" {
  type = string
}

variable "architectures" {
  type    = list(string)
  default = ["x86_64"]
}

# --- Artifact ---------------------------------------------------------------
# Terraform deploys whatever artifact is at this path (a stub on first apply,
# see ../../artifacts/) and then ignores future changes to it. Real code is
# pushed by the existing GitHub Actions workflows via
# `aws lambda update-function-code`, exactly as they do today — Terraform
# never fights CI for ownership of application code.

variable "artifact_path" {
  description = "Path to a zip (or jar) file to deploy initially."
  type        = string
}

variable "memory_size" {
  type    = number
  default = 256
}

variable "timeout" {
  type    = number
  default = 30
}

variable "environment_variables" {
  type    = map(string)
  default = {}
}

variable "additional_policy_statements" {
  description = "Extra IAM policy statements (in aws_iam_policy_document statement block form) granting this function access beyond basic CloudWatch Logs."
  type = list(object({
    sid       = optional(string)
    effect    = optional(string, "Allow")
    actions   = list(string)
    resources = list(string)
  }))
  default = []
}

variable "vpc_config" {
  description = "Set only for functions that need to reach RDS/Hasura directly (i.e. the backend). Leave null for the standalone scan/crawler/verapdf functions so they keep direct internet egress without NAT."
  type = object({
    subnet_ids         = list(string)
    security_group_ids = list(string)
  })
  default = null
}

variable "sqs_event_source" {
  description = "Set to wire an SQS trigger (scan-html/scan-pdf consuming their FIFO queues)."
  type = object({
    queue_arn                          = string
    batch_size                         = optional(number, 5)
    maximum_batching_window_in_seconds = optional(number, 0)
  })
  default = null
}

variable "create_function_url" {
  type    = bool
  default = false
}

variable "function_url_cors" {
  type = object({
    allow_origins = list(string)
    allow_methods = list(string)
    allow_headers = list(string)
  })
  default = null
}

variable "log_retention_in_days" {
  type    = number
  default = 30
}

variable "reserved_concurrent_executions" {
  description = "-1 means unreserved (default AWS behavior)."
  type        = number
  default     = -1
}

# --- Layers -------------------------------------------------------------
# Same pattern as the artifact above: starts empty/whatever's declared here,
# then scripts/deploy-app.sh attaches real layers (e.g. the @sparticuz/chromium
# binary for scan-html) via `aws lambda update-function-configuration
# --layers`. Terraform ignores drift on this attribute so it doesn't revert
# what the script attaches.

variable "layers" {
  type    = list(string)
  default = []
}
