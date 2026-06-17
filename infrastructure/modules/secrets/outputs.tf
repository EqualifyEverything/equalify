output "db_password_secret_arn" {
  value = aws_secretsmanager_secret.db_password.arn
}

output "db_password" {
  value     = random_password.db_password.result
  sensitive = true
}

output "hasura_admin_secret_arn" {
  value = aws_secretsmanager_secret.hasura_admin_secret.arn
}

output "hasura_admin_secret" {
  value     = random_password.hasura_admin_secret.result
  sensitive = true
}

output "webhook_secret_arn" {
  value = aws_secretsmanager_secret.webhook_secret.arn
}

output "webhook_secret" {
  value     = random_password.webhook_secret.result
  sensitive = true
}

output "sso_config_secret_arn" {
  value = var.sso_enabled ? aws_secretsmanager_secret.sso_config[0].arn : null
}
