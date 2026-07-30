output "frontend_url" {
  value = local.use_custom_domains ? "https://${local.frontend_domain}" : module.frontend_hosting.url
}

output "frontend_bucket_name" {
  description = "Target for `aws s3 sync ./dist s3://<this>` (matches apps/frontend's build:prod/build:staging scripts)."
  value       = module.frontend_hosting.bucket_name
}

output "deploy_artifacts_bucket_name" {
  description = "Transit bucket for large deploy artifacts (e.g. the Chromium layer) that exceed Lambda's direct-upload size limit — see scripts/deploy-app.sh."
  value       = aws_s3_bucket.deploy_artifacts.bucket
}

output "frontend_cloudfront_distribution_id" {
  description = "Target for `aws cloudfront create-invalidation --distribution-id <this>`."
  value       = module.frontend_hosting.distribution_id
}

output "api_url" {
  value = local.use_custom_domains ? "https://${local.api_domain}" : module.api_gateway.api_endpoint
}

output "graphql_url" {
  value = local.use_custom_domains ? "https://${local.graphql_domain}/v1/graphql" : module.hasura_ecs.graphql_url
}

output "graphql_wss_url" {
  value = local.use_custom_domains ? "wss://${local.graphql_domain}/v1/graphql" : module.hasura_ecs.graphql_wss_url
}

output "cognito_user_pool_id" {
  value = module.cognito.user_pool_id
}

output "cognito_web_client_id" {
  value = module.cognito.web_client_id
}

output "rds_endpoint" {
  value = module.rds.endpoint
}

output "rds_instance_id" {
  description = "Target for `aws rds modify-db-instance --db-instance-identifier <this>` (e.g. to disable deletion protection before a teardown)."
  value       = module.rds.db_instance_id
}

output "db_name" {
  value = module.rds.db_name
}

output "db_username" {
  value = var.db_username
}

output "db_password_secret_arn" {
  description = "Fetch the actual value with: aws secretsmanager get-secret-value --secret-id <this>"
  value       = module.secrets.db_password_secret_arn
}

output "bastion_instance_id" {
  description = "Target for `aws ssm start-session --target <this> ...` — see scripts/deploy-app.sh."
  value       = module.bastion.instance_id
}

output "webhook_secret_arn" {
  description = "Fetch the actual value with: aws secretsmanager get-secret-value --secret-id <this>"
  value       = module.secrets.webhook_secret_arn
}

output "sso_config_secret_arn" {
  description = "Null unless sso_enabled = true. Fetch the actual value with: aws secretsmanager get-secret-value --secret-id <this>"
  value       = module.secrets.sso_config_secret_arn
}

output "lambda_function_names" {
  value = {
    scan_sqs_router   = module.lambda_scan_sqs_router.function_name
    scan_html         = module.lambda_scan_html.function_name
    scan_pdf          = module.lambda_scan_pdf.function_name
    verapdf_interface = module.lambda_verapdf_interface.function_name
    crawler           = module.lambda_crawler.function_name
    backend           = module.lambda_backend.function_name
  }
}

output "crawler_function_url" {
  description = "Invoke URL for the crawler Lambda (called directly from the frontend)."
  value       = module.lambda_crawler.function_url
}

output "hasura_admin_secret_arn" {
  description = "Fetch the actual value with: aws secretsmanager get-secret-value --secret-id <this>"
  value       = module.secrets.hasura_admin_secret_arn
}

output "frontend_env_hints" {
  description = "Values to populate apps/frontend's .env.production / .env.staging with before running its build:prod/build:staging scripts."
  value = {
    VITE_API_URL = local.use_custom_domains ? "https://${local.api_domain}" : module.api_gateway.api_endpoint
    # apps/frontend's own code (App.tsx, Logo.tsx, useSubscription.ts) always
    # appends /v1/graphql itself, so these need to be the bare domain — trim
    # it back off graphql_url/graphql_wss_url, which include it (useful for
    # direct display/copy-paste, and what scripts/deploy-app.sh's own
    # metadata-apply step expects).
    VITE_GRAPHQL_URL         = trimsuffix(local.use_custom_domains ? "https://${local.graphql_domain}/v1/graphql" : module.hasura_ecs.graphql_url, "/v1/graphql")
    VITE_GRAPHQL_WSS         = trimsuffix(local.use_custom_domains ? "wss://${local.graphql_domain}/v1/graphql" : module.hasura_ecs.graphql_wss_url, "/v1/graphql")
    VITE_USERPOOLID          = module.cognito.user_pool_id
    VITE_USERPOOLWEBCLIENTID = module.cognito.web_client_id
  }
}
