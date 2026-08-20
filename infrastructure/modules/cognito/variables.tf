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

variable "backend_lambda_function_name" {
  description = "Real reference to the backend Lambda's function name (apps/backend — also handles PreSignUp/PostConfirmation/PreTokenGeneration Cognito triggers via its top-level event.triggerSource dispatch). Only used for the invoke permission below, which safely depends on the real Lambda already existing."
  type        = string
}

variable "backend_lambda_predicted_arn" {
  description = <<-EOT
    Deterministically-constructed ARN (region + account id + the known
    function-name pattern) for the backend Lambda, NOT a direct reference to
    its resource output. lambda_config below can't reference the real
    Lambda's output: that Lambda's own env vars need this user pool's
    id/client_id, so a real reference here would form a dependency cycle.
    Cognito doesn't validate the Lambda ARN's existence when lambda_config
    is set, so the predicted value is safe to use immediately.
  EOT
  type        = string
}
