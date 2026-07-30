locals {
  name = "${var.project_name}-${var.environment}"
}

resource "aws_cognito_user_pool" "this" {
  name = local.name

  password_policy {
    minimum_length    = 12
    require_lowercase = true
    require_uppercase = true
    require_numbers   = true
    require_symbols   = false
  }

  auto_verified_attributes = ["email"]

  username_attributes = ["email"]

  schema {
    name                = "email"
    attribute_data_type = "String"
    required            = true
    mutable             = true
  }

  account_recovery_setting {
    recovery_mechanism {
      name     = "verified_email"
      priority = 1
    }
  }

  # apps/backend's index.ts dispatches on event.triggerSource to
  # routes/cognito/{preSignUpSignUp,postConfirmationConfirmSignUp,tokenGeneration}.ts
  # — without these wired up, new native-Cognito users are never synced into
  # the users table (postConfirmationConfirmSignUp) and never get Hasura JWT
  # claims (tokenGeneration), breaking auth almost entirely for non-SSO use.
  lambda_config {
    pre_sign_up          = var.backend_lambda_predicted_arn
    post_confirmation    = var.backend_lambda_predicted_arn
    pre_token_generation = var.backend_lambda_predicted_arn
  }
}

resource "aws_lambda_permission" "cognito_invoke" {
  statement_id  = "AllowCognitoInvoke"
  action        = "lambda:InvokeFunction"
  function_name = var.backend_lambda_function_name
  principal     = "cognito-idp.amazonaws.com"
  source_arn    = aws_cognito_user_pool.this.arn
}

# Matches VITE_USERPOOLWEBCLIENTID usage in apps/frontend — an SPA public
# client, no client secret, ALLOW_USER_SRP_AUTH for amplify's default auth flow.
resource "aws_cognito_user_pool_client" "web" {
  name         = "${local.name}-web"
  user_pool_id = aws_cognito_user_pool.this.id

  generate_secret = false

  explicit_auth_flows = [
    "ALLOW_USER_SRP_AUTH",
    "ALLOW_REFRESH_TOKEN_AUTH",
  ]

  allowed_oauth_flows_user_pool_client = true
  allowed_oauth_flows                  = ["code"]
  allowed_oauth_scopes                 = ["openid", "email", "profile"]

  callback_urls = var.callback_urls
  logout_urls   = var.logout_urls

  supported_identity_providers = ["COGNITO"]
}

resource "aws_cognito_user_pool_domain" "this" {
  domain       = var.cognito_domain_prefix
  user_pool_id = aws_cognito_user_pool.this.id
}
