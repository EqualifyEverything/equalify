variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "subnet_id" {
  description = "Private subnet to launch the bastion into (from the networking module)."
  type        = string
}

variable "security_group_id" {
  description = "SSM-only security group (egress only, no ingress) from the networking module."
  type        = string
}

variable "instance_type" {
  description = "Only used as an SSM Session Manager relay for one-off DB access (schema load, migrations) — a small instance is intentional."
  type        = string
  default     = "t3.micro"
}
