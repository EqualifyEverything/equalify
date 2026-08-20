variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "domain_name" {
  description = "Custom domain for the frontend (e.g. app.example.com). Leave null to use the default CloudFront domain."
  type        = string
  default     = null
}

variable "acm_certificate_arn" {
  description = "ACM certificate ARN for domain_name, required if domain_name is set. MUST be issued in us-east-1 (CloudFront requirement) regardless of the stack's primary region."
  type        = string
  default     = null
}

variable "price_class" {
  type    = string
  default = "PriceClass_100"
}
