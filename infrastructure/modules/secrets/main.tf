locals {
  name = "${var.project_name}/${var.environment}"
}

# --- Generated secrets ------------------------------------------------------
# These are values the app only ever reads as plain env vars
# (`process.env.DB_PASSWORD`, etc.) — Terraform generates and stores them,
# no application code changes are required.

resource "random_password" "db_password" {
  length  = 32
  special = false # serverless-postgres / RDS connection strings choke on some special chars
}

resource "aws_secretsmanager_secret" "db_password" {
  name                    = "${local.name}/db-password"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "db_password" {
  secret_id     = aws_secretsmanager_secret.db_password.id
  secret_string = random_password.db_password.result
}

resource "random_password" "hasura_admin_secret" {
  length  = 32
  special = false
}

resource "aws_secretsmanager_secret" "hasura_admin_secret" {
  name                    = "${local.name}/hasura-admin-secret"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "hasura_admin_secret" {
  secret_id     = aws_secretsmanager_secret.hasura_admin_secret.id
  secret_string = random_password.hasura_admin_secret.result
}

resource "random_password" "webhook_secret" {
  length  = 32
  special = false
}

resource "aws_secretsmanager_secret" "webhook_secret" {
  name                    = "${local.name}/webhook-secret"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "webhook_secret" {
  secret_id     = aws_secretsmanager_secret.webhook_secret.id
  secret_string = random_password.webhook_secret.result
}

# --- SSO (Azure AD) config, optional ----------------------------------------
# Real tenant config must be supplied by the deployer post-apply; Terraform
# only reserves the Secrets Manager entry when sso_enabled is true.

resource "aws_secretsmanager_secret" "sso_config" {
  count = var.sso_enabled ? 1 : 0

  name                    = "${local.name}/sso-config"
  recovery_window_in_days = 7
}

resource "aws_secretsmanager_secret_version" "sso_config" {
  count = var.sso_enabled ? 1 : 0

  secret_id = aws_secretsmanager_secret.sso_config[0].id
  secret_string = jsonencode({
    SSO_JWKS          = "REPLACE_ME"
    SSO_CLIENT_ID     = "REPLACE_ME"
    SSO_TENANT        = "REPLACE_ME"
    SSO_EMAIL_DOMAINS = "[]"
  })

  lifecycle {
    ignore_changes = [secret_string]
  }
}
