variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "sso_enabled" {
  description = "Whether to provision placeholder SSO (Azure AD) secret entries. Real tenant values must be filled in manually post-apply since Terraform cannot provision a third party's Azure tenant."
  type        = bool
  default     = false
}
