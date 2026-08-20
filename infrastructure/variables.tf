variable "aws_region" {
  description = "Primary AWS region for the stack."
  type        = string
  default     = "us-east-2"
}

variable "project_name" {
  description = "Short name used as a prefix for resource names/tags."
  type        = string
  default     = "equalify"
}

variable "environment" {
  description = "Environment name, e.g. prod or staging. One Terraform apply = one environment (see infrastructure/README.md)."
  type        = string
  default     = "prod"
}

# --- Optional custom domain --------------------------------------------------
# Leave domain_name null to deploy entirely on AWS-issued default endpoints
# (CloudFront domain, ALB DNS name, API Gateway default endpoint) — no
# pre-owned DNS required. Set both domain_name and route53_zone_id to get
# app.<domain_name> / api.<domain_name> / graphql.<domain_name> with
# Terraform-managed ACM certs and Route53 records.

variable "domain_name" {
  description = "Base domain, e.g. \"example.com\". Subdomains app./api./graphql. are derived from it."
  type        = string
  default     = null
}

variable "route53_zone_id" {
  description = "Route53 hosted zone ID for domain_name. Required (alongside domain_name) to provision custom-domain ACM certs and DNS records."
  type        = string
  default     = null
}

# --- Cognito ------------------------------------------------------------------

variable "cognito_domain_prefix" {
  description = "Prefix for the Cognito Hosted UI domain (<prefix>.auth.<region>.amazoncognito.com). Must be globally unique within the region."
  type        = string
}

# --- App configuration (mirrors apps/backend & apps/frontend env vars) -------

variable "app_url" {
  description = "Frontend URL the backend uses in emails/links. Defaults to the derived custom domain or CloudFront URL if left null."
  type        = string
  default     = null
}

variable "brand_url" {
  type    = string
  default = "https://equalify.app/"
}

variable "brand_logo_url" {
  type    = string
  default = "https://equalify.app/wp-content/uploads/2024/04/Equalify-Logo-768x237.png"
}

variable "ses_admin_email" {
  description = "From-address for transactional email. Must be a verified SES identity in this account/region."
  type        = string
}

variable "sso_enabled" {
  description = "Enable Microsoft Azure AD SSO support. Real tenant config must be supplied manually post-apply (see secrets module)."
  type        = bool
  default     = false
}

# --- Networking -----------------------------------------------------------

variable "vpc_cidr" {
  type    = string
  default = "10.20.0.0/16"
}

variable "single_nat_gateway" {
  type    = bool
  default = true
}

# --- RDS --------------------------------------------------------------------

variable "db_name" {
  type    = string
  default = "equalify"
}

variable "db_username" {
  type    = string
  default = "equalify_admin"
}

variable "db_instance_class" {
  type    = string
  default = "db.t4g.micro"
}

variable "db_allocated_storage" {
  type    = number
  default = 20
}

variable "db_multi_az" {
  type    = bool
  default = false
}

variable "db_deletion_protection" {
  type    = bool
  default = true
}

variable "db_skip_final_snapshot" {
  description = "Set true only for throwaway/test stacks."
  type        = bool
  default     = false
}

# --- Hasura (ECS Fargate) ----------------------------------------------------

variable "hasura_image" {
  type    = string
  default = "hasura/graphql-engine:v2.42.0"
}

variable "hasura_cpu" {
  type    = number
  default = 512
}

variable "hasura_memory" {
  type    = number
  default = 1024
}

variable "hasura_desired_count" {
  type    = number
  default = 1
}

# --- Bastion (SSM-only, for one-off DB access) -------------------------------

variable "bastion_instance_type" {
  description = "Only used as an SSM Session Manager relay for scripts/deploy-app.sh's DB access (schema load, migrations) — a small instance is intentional."
  type        = string
  default     = "t3.micro"
}

# --- Lambda sizing ------------------------------------------------------------

variable "scan_lambda_memory_size" {
  description = "Memory for the headless-browser scan Lambdas (scan-html/scan-pdf). Puppeteer/Chromium needs headroom."
  type        = number
  default     = 2048
}

variable "scan_lambda_timeout" {
  type    = number
  default = 120
}

variable "backend_lambda_memory_size" {
  type    = number
  default = 512
}

variable "backend_lambda_timeout" {
  type    = number
  default = 30
}

# --- Monitoring ---------------------------------------------------------------

variable "alarm_email" {
  description = "Email to subscribe to the CloudWatch alarms SNS topic. Leave null to skip."
  type        = string
  default     = null
}

# --- Safety -------------------------------------------------------------------

variable "allowed_account_ids" {
  description = "AWS account IDs this configuration may run against. Set to your target account so credentials pointing anywhere else (e.g. a prod profile left in the shell) fail at plan time instead of touching the wrong account. Empty list = no restriction."
  type        = list(string)
  default     = []
}
