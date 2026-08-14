# Equalify Infrastructure (Terraform)

Provisions a complete, self-contained AWS environment for Equalify: RDS
(PostgreSQL), the 6 Lambda functions in `services/` and `apps/backend`, SQS
FIFO queues, Hasura GraphQL Engine (ECS Fargate + ALB), Cognito, the
frontend's S3 + CloudFront static hosting, and a small SSM-only bastion for
one-off DB access — with least-privilege IAM throughout.

Terraform owns **infrastructure only**. Application code is still pushed by
the existing GitHub Actions workflows (`aws lambda update-function-code`,
`npm run build:prod`'s `aws s3 sync`) — see [Artifact handoff](#artifact-handoff)
below.

## Prerequisites

- Terraform >= 1.5
- An AWS account and credentials with sufficient privileges (VPC, RDS, ECS,
  Lambda, S3, CloudFront, Cognito, IAM, Route53/ACM if using a custom domain)
- A verified SES identity (domain or single address) in the target
  account/region — required for `ses_admin_email` to actually send mail

## 1. Bootstrap remote state

```
cd bootstrap
terraform init
terraform apply
terraform output
```

Note the `state_bucket_name`, `lock_table_name`, and `aws_region` outputs.

## 2. Configure the root module's backend

```
cd ..
terraform init \
  -backend-config="bucket=<state_bucket_name>" \
  -backend-config="dynamodb_table=<lock_table_name>" \
  -backend-config="region=<aws_region>"
```

## 3. Configure variables

```
cp terraform.tfvars.example terraform.tfvars
```

Fill in `cognito_domain_prefix` and `ses_admin_email` at minimum. See
`terraform.tfvars.example` for every other knob (custom domain, sizing,
monitoring).

## 4. Apply

```
terraform plan
terraform apply
```

This single apply provisions one full environment. To run a second
environment (e.g. staging), use a separate AWS account or a separate
Terraform state (workspace, or a different `-backend-config`/`key`) with
`environment = "staging"` in its tfvars — the module does not hardcode a
prod/staging split.

## 5. Day-2: deploy real application code

`terraform apply` creates each Lambda with a no-op stub (see
[Artifact handoff](#artifact-handoff)) and the frontend bucket empty. Push
real code with the existing tooling, pointed at the names `terraform output`
just gave you:

- **Lambdas**: the existing `.github/workflows/deploy-aws-lambda-*.yml` and
  `deploy-apps.yml` workflows already build + `aws lambda update-function-code`
  against function names matching this module's outputs
  (`terraform output lambda_function_names`). Update the workflow's target
  function names if you changed `project_name`/`environment` from the
  defaults.
- **Frontend**: populate `apps/frontend/.env.production.local` (not
  `.env.production`/`.env.staging` directly — those hold the real org's
  committed config for the existing GitHub Actions deploy;
  `.env.production.local` takes precedence in Vite and is already
  gitignored) with `terraform output frontend_env_hints`, then run the
  existing `npm run build:prod` script — it already does `vite build` +
  `aws s3 sync` + CloudFront invalidation against `frontend_bucket_name` /
  `frontend_cloudfront_distribution_id`.
- **Database schema**: this stack provisions an empty RDS instance only.
  Run `db/schema.sql` (and the `pgcrypto` extension it depends on) plus
  anything in `db/migrations/` against `terraform output rds_endpoint`
  yourself — schema management is intentionally outside this module's scope.
- **Hasura metadata**: apply `db/hasura-metadata.json` against
  `terraform output graphql_url` using the Hasura CLI, authenticated with the
  admin secret from `terraform output hasura_admin_secret_arn`.
- **SSO (optional)**: if `sso_enabled = true`, fill in the real Azure AD
  tenant config in the `<project>/<env>/sso-config` Secrets Manager entry
  the same way, then re-apply.

## Artifact handoff

Terraform needs *some* zip/jar to create each Lambda function. Rather than
have Terraform invoke `esbuild`/Maven itself (mixing build and infra
concerns), `artifacts/node-stub.zip` and `artifacts/java-stub.jar` are
minimal no-op handlers deployed on first `apply`. The `lambda_function`
module sets `lifecycle { ignore_changes = [filename, source_code_hash] }` on
every function, so subsequent `terraform apply` runs never revert code
pushed by CI. Terraform and CI never fight over ownership of application
code — Terraform owns the function/role/triggers, CI owns the code inside it.

## Module layout

| Module | Provisions |
|---|---|
| `modules/networking` | VPC, 2 AZ public+private subnets, NAT, security group chain |
| `modules/secrets` | Secrets Manager: DB password, Hasura admin secret, webhook secret, optional SSO config |
| `modules/rds` | PostgreSQL 17.5 instance, private subnet group |
| `modules/bastion` | SSM-only EC2 instance (no SSH key, no public IP, no inbound rules) for `scripts/deploy-app.sh`'s one-off DB access to the otherwise fully private RDS instance |
| `modules/cognito` | User pool + SPA app client + hosted UI domain |
| `modules/sqs` | `scanHtml.fifo` / `scanPdf.fifo` + DLQs |
| `modules/lambda_function` | Reusable: IAM role, log group, optional VPC attach, optional SQS trigger, optional Function URL |
| `modules/api_gateway` | HTTP API (API Gateway v2) fronting the backend Lambda |
| `modules/hasura_ecs` | ECS Fargate service running Hasura, behind an ALB |
| `modules/frontend_hosting` | Private S3 bucket + CloudFront (OAC), SPA routing fallback |
| `modules/monitoring` | SNS alarm topic, Lambda error / RDS storage / Hasura health alarms |

## IAM, intentionally narrower than the original AWS account

The original deployment's exported policies (`/equalify-aws-data/lambda/*.json`)
grant broad wildcards (`sqs:*`, `s3:*`, `dynamodb:*`, `cognito-idp:*` on `*`,
etc.) that don't match what the application code actually calls. This module
scopes every Lambda's permissions to what its code in `services/*` and
`apps/backend` actually uses — e.g. `scan-pdf` can only `lambda:InvokeFunction`
the specific `verapdf-interface` function ARN, not all functions in the
account. `s3:*`/`dynamodb:*` are dropped entirely since no code path uses
them; re-add them via a given Lambda module call's
`additional_policy_statements` if a future feature needs them.

One exception: `services/aws-lambda-crawler` ships with an IAM export
(`aws-lambda-crawler.json`) that's actually a duplicate of
`aws-lambda-scan-html`'s role (same role name, same log group ARN, grants
unused SQS permissions). The crawler's own code only does outbound HTTP
fetches — it gets a logs-only role here, and is triggered via a CORS-enabled
Lambda Function URL (matching the explicit OPTIONS-preflight handling already
in its handler) rather than SQS.
