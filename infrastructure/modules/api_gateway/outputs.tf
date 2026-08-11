output "api_endpoint" {
  value = aws_apigatewayv2_api.this.api_endpoint
}

output "api_id" {
  value = aws_apigatewayv2_api.this.id
}

output "custom_domain_target" {
  description = "Target hostname for a Route53 ALIAS/CNAME record pointing domain_name at this API, if domain_name was set."
  value       = var.domain_name != null ? aws_apigatewayv2_domain_name.this[0].domain_name_configuration[0].target_domain_name : null
}

output "custom_domain_hosted_zone_id" {
  description = "Hosted zone ID for a Route53 ALIAS record pointing domain_name at this API, if domain_name was set."
  value       = var.domain_name != null ? aws_apigatewayv2_domain_name.this[0].domain_name_configuration[0].hosted_zone_id : null
}
