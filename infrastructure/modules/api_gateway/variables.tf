variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "backend_lambda_function_name" {
  type = string
}

variable "backend_lambda_invoke_arn" {
  type = string
}

variable "domain_name" {
  description = "Custom domain for the API (e.g. api.example.com). Leave null to use the default API Gateway endpoint."
  type        = string
  default     = null
}

variable "acm_certificate_arn" {
  description = "ACM certificate ARN for domain_name, required if domain_name is set. Must be in the same region as the API Gateway (regional endpoint), not us-east-1."
  type        = string
  default     = null
}

variable "cors_allow_origins" {
  type    = list(string)
  default = ["*"]
}

variable "log_retention_in_days" {
  type    = number
  default = 30
}
