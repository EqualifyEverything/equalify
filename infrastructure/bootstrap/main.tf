/*
 * Bootstrap stack: creates the S3 bucket + DynamoDB lock table used as the
 * remote state backend for the main `infrastructure/` root module.
 *
 * This stack is applied once, on its own (local) state, before the root
 * module is initialized — it solves the chicken-and-egg problem of needing
 * a bucket to store state about a bucket.
 *
 *   cd bootstrap
 *   terraform init
 *   terraform apply
 *   terraform output
 *
 * Take the `state_bucket_name` and `lock_table_name` outputs and use them
 * to configure the root module's backend (see ../README.md).
 */

terraform {
  required_version = ">= 1.5.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

variable "aws_region" {
  description = "AWS region to create the state bucket and lock table in."
  type        = string
  default     = "us-east-2"
}

variable "project_name" {
  description = "Short name used as a prefix for the bootstrap resources. Must be globally unique enough that the resulting S3 bucket name is available."
  type        = string
  default     = "equalify"
}

resource "random_id" "suffix" {
  byte_length = 4
}

resource "aws_s3_bucket" "terraform_state" {
  bucket = "${var.project_name}-tfstate-${random_id.suffix.hex}"

  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_s3_bucket_versioning" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_dynamodb_table" "terraform_lock" {
  name         = "${var.project_name}-tfstate-lock"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "LockID"

  attribute {
    name = "LockID"
    type = "S"
  }
}

output "state_bucket_name" {
  description = "S3 bucket name to use as the root module's backend `bucket`."
  value       = aws_s3_bucket.terraform_state.id
}

output "lock_table_name" {
  description = "DynamoDB table name to use as the root module's backend `dynamodb_table`."
  value       = aws_dynamodb_table.terraform_lock.name
}

output "aws_region" {
  description = "Region to use as the root module's backend `region`."
  value       = var.aws_region
}
