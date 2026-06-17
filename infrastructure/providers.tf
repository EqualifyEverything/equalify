provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = "equalify"
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}

# CloudFront requires ACM certificates to exist in us-east-1, regardless of
# where the rest of the stack is deployed. Only used when var.domain_name
# is set and frontend_hosting needs a custom-domain certificate.
provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"

  default_tags {
    tags = {
      Project     = "equalify"
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}
