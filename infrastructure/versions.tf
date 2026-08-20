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

  # Configure after running `bootstrap/` and obtaining its outputs. Terraform
  # does not support variables in the backend block, so these placeholders
  # must be filled in directly here or passed via `-backend-config` flags:
  #
  #   terraform init \
  #     -backend-config="bucket=<state_bucket_name>" \
  #     -backend-config="dynamodb_table=<lock_table_name>" \
  #     -backend-config="region=<aws_region>"
  backend "s3" {
    key     = "equalify/terraform.tfstate"
    encrypt = true
  }
}
