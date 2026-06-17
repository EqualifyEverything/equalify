variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "vpc_id" {
  type = string
}

variable "private_subnet_ids" {
  type = list(string)
}

variable "security_group_id" {
  description = "Security group allowing 5432 from the backend Lambda and Hasura task SGs (from the networking module)."
  type        = string
}

variable "db_name" {
  description = "Initial database name. Matches DB_NAME consumed by apps/backend."
  type        = string
  default     = "equalify"
}

variable "db_username" {
  description = "Master username. Matches DB_USER consumed by apps/backend."
  type        = string
  default     = "equalify_admin"
}

variable "db_password" {
  description = "Master password, sourced from the secrets module."
  type        = string
  sensitive   = true
}

variable "engine_version" {
  description = "PostgreSQL engine version. Matches the schema dumped from db/schema.sql (17.5)."
  type        = string
  default     = "17.5"
}

variable "instance_class" {
  type    = string
  default = "db.t4g.micro"
}

variable "allocated_storage" {
  type    = number
  default = 20
}

variable "max_allocated_storage" {
  description = "Upper bound for RDS storage autoscaling."
  type        = number
  default     = 100
}

variable "multi_az" {
  type    = bool
  default = false
}

variable "backup_retention_period" {
  type    = number
  default = 7
}

variable "deletion_protection" {
  type    = bool
  default = true
}

variable "skip_final_snapshot" {
  description = "Set true only for throwaway/test stacks — disables the final snapshot on destroy."
  type        = bool
  default     = false
}
