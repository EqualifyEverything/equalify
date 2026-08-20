output "alb_dns_name" {
  value = aws_lb.this.dns_name
}

output "alb_zone_id" {
  description = "For a Route53 ALIAS record, if domain_name was set on this module."
  value       = aws_lb.this.zone_id
}

output "graphql_url" {
  description = "GraphQL HTTP endpoint: the custom domain if set, otherwise the CloudFront HTTPS front for the ALB."
  value       = var.domain_name != null ? "https://${var.domain_name}/v1/graphql" : "https://${aws_cloudfront_distribution.this[0].domain_name}/v1/graphql"
}

output "graphql_wss_url" {
  description = "GraphQL WebSocket endpoint: the custom domain if set, otherwise the same CloudFront distribution (wss, passed through unchanged)."
  value       = var.domain_name != null ? "wss://${var.domain_name}/v1/graphql" : "wss://${aws_cloudfront_distribution.this[0].domain_name}/v1/graphql"
}

output "cluster_name" {
  value = aws_ecs_cluster.this.name
}

output "service_name" {
  value = aws_ecs_service.this.name
}

output "alb_arn_suffix" {
  description = "For CloudWatch ALB metrics (AWS/ApplicationELB)."
  value       = aws_lb.this.arn_suffix
}

output "target_group_arn_suffix" {
  description = "For CloudWatch target group metrics (AWS/ApplicationELB)."
  value       = aws_lb_target_group.this.arn_suffix
}
