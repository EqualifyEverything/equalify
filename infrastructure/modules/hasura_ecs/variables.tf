variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "vpc_id" {
  type = string
}

variable "public_subnet_ids" {
  description = "Subnets for the internet-facing ALB."
  type        = list(string)
}

variable "private_subnet_ids" {
  description = "Subnets for the Fargate tasks."
  type        = list(string)
}

variable "alb_security_group_id" {
  type = string
}

variable "task_security_group_id" {
  type = string
}

variable "hasura_image" {
  description = "Hasura GraphQL Engine image. Pin to a specific version, not :latest, for reproducible deploys."
  type        = string
  default     = "hasura/graphql-engine:v2.42.0"
}

variable "database_url" {
  description = "Full postgres:// connection string for HASURA_GRAPHQL_DATABASE_URL."
  type        = string
  sensitive   = true
}

variable "admin_secret" {
  description = "HASURA_GRAPHQL_ADMIN_SECRET value (from the secrets module)."
  type        = string
  sensitive   = true
}

variable "cpu" {
  type    = number
  default = 512
}

variable "memory" {
  type    = number
  default = 1024
}

variable "desired_count" {
  type    = number
  default = 1
}

variable "domain_name" {
  description = "Custom domain for Hasura (e.g. graphql.example.com). Leave null to use the ALB's default DNS name."
  type        = string
  default     = null
}

variable "acm_certificate_arn" {
  description = "ACM certificate ARN for domain_name's HTTPS listener, required if domain_name is set. Must be in the same region as the ALB."
  type        = string
  default     = null
}

variable "log_retention_in_days" {
  type    = number
  default = 30
}

variable "cors_allowed_origins" {
  description = "Value for HASURA_GRAPHQL_CORS_DOMAIN, comma-separated origins allowed to call the GraphQL API (the frontend's domain)."
  type        = string
  default     = "*"
}
