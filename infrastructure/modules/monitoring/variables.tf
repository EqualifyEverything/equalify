variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "alarm_email" {
  description = "Email to subscribe to the alarms SNS topic. Leave null to skip the subscription (you can add one later)."
  type        = string
  default     = null
}

variable "lambda_function_names" {
  description = "Lambda function names to alarm on Errors for."
  type        = list(string)
}

variable "rds_instance_id" {
  type = string
}

variable "alb_arn_suffix" {
  type = string
}

variable "target_group_arn_suffix" {
  type = string
}

variable "ecs_cluster_name" {
  type = string
}

variable "ecs_service_name" {
  type = string
}
