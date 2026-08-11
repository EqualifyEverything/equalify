output "bucket_name" {
  value = aws_s3_bucket.this.id
}

output "distribution_id" {
  value = aws_cloudfront_distribution.this.id
}

output "distribution_domain_name" {
  value = aws_cloudfront_distribution.this.domain_name
}

output "url" {
  value = var.domain_name != null ? "https://${var.domain_name}" : "https://${aws_cloudfront_distribution.this.domain_name}"
}
