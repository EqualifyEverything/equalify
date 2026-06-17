variable "project_name" {
  description = "Short name used as a prefix for resource names/tags."
  type        = string
}

variable "environment" {
  description = "Environment name (e.g. prod, staging) used in resource names/tags."
  type        = string
}

variable "vpc_cidr" {
  description = "CIDR block for the VPC."
  type        = string
  default     = "10.20.0.0/16"
}

variable "az_count" {
  description = "Number of availability zones to spread public/private subnets across. RDS subnet groups and ALBs both require at least 2."
  type        = number
  default     = 2

  validation {
    condition     = var.az_count >= 2
    error_message = "At least 2 availability zones are required for the RDS subnet group and the Hasura ALB."
  }
}

variable "single_nat_gateway" {
  description = "If true, create a single shared NAT Gateway instead of one per AZ. Cheaper, but is a single point of failure for private-subnet egress (backend Lambda, Hasura outbound calls)."
  type        = bool
  default     = true
}
