variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "callback_urls" {
  description = "OAuth callback URLs for the SPA app client (the frontend's CloudFront/custom domain)."
  type        = list(string)
}

variable "logout_urls" {
  description = "OAuth logout URLs for the SPA app client."
  type        = list(string)
}

variable "cognito_domain_prefix" {
  description = "Prefix for the Cognito Hosted UI domain (<prefix>.auth.<region>.amazoncognito.com). Must be globally unique."
  type        = string
}
