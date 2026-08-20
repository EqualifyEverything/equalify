variable "project_name" {
  type = string
}

variable "environment" {
  type = string
}

variable "max_receive_count" {
  description = "Number of receive attempts before a message is moved to its DLQ."
  type        = number
  default     = 5
}

variable "visibility_timeout_seconds" {
  description = "Should be >= the consuming Lambda's timeout. scan-html/scan-pdf do headless-browser scans, default generously."
  type        = number
  default     = 180
}
