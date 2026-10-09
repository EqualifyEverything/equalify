# Quickstart: Installing Your Own Instance of Equalify

This guide provisions a complete, self-contained instance of Equalify in your
own AWS account — database, scan Lambdas, GraphQL API (Hasura), backend API,
and web frontend — then deploys the application code into it.

> **Cost:** a running instance incurs hourly AWS charges (RDS, NAT gateway,
> Hasura on ECS) even when idle. If you're just evaluating, see
> [Cleanup](#cleanup-avoid-ongoing-aws-charges) when you're done.

## Prerequisites

**AWS**

- An AWS account and credentials with permission to manage VPC, RDS, ECS,
  Lambda, S3, CloudFront, Cognito, SQS, Secrets Manager, IAM, and (for a
  custom domain) Route53/ACM.
- Those credentials active in your shell so Terraform and the scripts can see
  them, e.g. `export AWS_PROFILE=<your-profile>`. Run
  `aws sts get-caller-identity` to confirm. If Terraform can't find them, see
  [Troubleshooting](docs/quickstart/troubleshooting.md).
- A [verified SES identity](https://docs.aws.amazon.com/ses/latest/dg/verify-addresses-and-domains.html)
  (domain or single address) in the region you're deploying to. Equalify
  uses it to send invite emails and scan summaries. New SES accounts start
  in the [sandbox](https://docs.aws.amazon.com/ses/latest/dg/request-production-access.html),
  where they can only send to verified addresses, so you'll need to request
  production access before you can invite other users by email.

**Local tools** (macOS, Linux, or WSL on Windows)

- [Terraform](https://developer.hashicorp.com/terraform/install) >= 1.5, or
  [OpenTofu](https://opentofu.org/). On macOS, install with
  `brew tap hashicorp/tap && brew install hashicorp/tap/terraform` (the core
  Homebrew formula was removed).
- [AWS CLI v2](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
  and its [Session Manager plugin](https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html)
- Node.js 22.x and npm
- Java 17 and Maven
- `psql` (any recent PostgreSQL client)
- `jq`, `zip`, and `openssl`

## 1. Clone the repo and install dependencies

```
git clone https://github.com/equalifyEverything/equalify.git
cd equalify
npm install
```

## 2. Bootstrap Terraform remote state

Create the S3 bucket and DynamoDB table that Terraform uses to store its
state:

```
cd infrastructure/bootstrap
terraform init
terraform apply
terraform output
```

This uses `us-east-2` by default. To use a different region, run
`terraform apply -var aws_region=<region>` instead, and use the same region
in Step 4.

Note the `state_bucket_name`, `lock_table_name`, and `aws_region` outputs
for the next step.

## 3. Configure the root module's backend

```
cd ..
terraform init \
  -backend-config="bucket=<state_bucket_name>" \
  -backend-config="dynamodb_table=<lock_table_name>" \
  -backend-config="region=<aws_region>"
```

## 4. Configure variables

```
cp terraform.tfvars.example terraform.tfvars
```

Edit `terraform.tfvars` and set:

- `cognito_domain_prefix`: must be globally unique (it becomes
  `<prefix>.auth.<region>.amazoncognito.com`)
- `ses_admin_email`: must match your verified SES identity
- `aws_region`: only if you're not using `us-east-2`; must match Step 2 and
  your SES identity's region

Everything else has a working default. The comments in
`terraform.tfvars.example` cover the optional settings: custom domain,
instance sizing, Azure AD SSO, and CloudWatch alarm email.

## 5. Provision the infrastructure

```
terraform plan
terraform apply
```

This creates the full environment: RDS (PostgreSQL), 6 Lambda functions, SQS
queues, Hasura (ECS Fargate + ALB), Cognito, S3 + CloudFront for the
frontend, and a small bastion instance for database access. The Lambdas
start as no-op stubs and the frontend bucket is empty until the next step.

## 6. Deploy the application

From the repo root, deploy the code and create your first user:

```
cd ..
./scripts/deploy-app.sh --create-user you@example.com
```

The script reads everything it needs from `terraform output`, then loads the
database schema and migrations, applies the Hasura metadata, builds and
pushes all 6 Lambdas, and publishes the frontend.

`--create-user` prints an email and password to the terminal. **Save them —
they aren't shown again.** The first user automatically becomes an admin.

Other options (run with `--help` for the full list):

| Option | Effect |
| --- | --- |
| `--skip-db` | Skip loading the schema and migrations |
| `--skip-hasura` | Skip applying Hasura metadata |
| `--skip-lambdas` | Skip building and pushing the Lambdas |
| `--skip-frontend` | Skip building and publishing the frontend |

Re-run the script any time to ship code updates. To run each stage by hand
instead, see [Manual deploy steps](docs/quickstart/manual-deploy.md).

## 7. Log in and run your first scan

1. Get your app's URL with `terraform -chdir=infrastructure output frontend_url`.
2. Sign in with the email and password from Step 6.
3. Submit a URL to scan.

If you didn't create a user in Step 6, create one without redeploying:

```
./scripts/deploy-app.sh --skip-db --skip-hasura --skip-lambdas --skip-frontend \
  --create-user you@example.com
```

## Cleanup (avoid ongoing AWS charges)

> **Warning:** this permanently deletes all scan data, audits, and users.
> Only run it against a disposable test or evaluation instance.

```
./scripts/destroy-app.sh
```

This removes every resource created in this guide. It asks you to type a
confirmation before deleting anything.

| Option | Effect |
| --- | --- |
| `--yes` | Skip the confirmation prompts (for scripted/CI use) |
| `--skip-final-snapshot` | Don't keep a final RDS snapshot |
| `--destroy-bootstrap` | Also delete the Terraform state bucket and lock table from Step 2 (kept by default; they cost a few cents a month) |

To run each step by hand instead, see
[Manual cleanup steps](docs/quickstart/manual-cleanup.md).

## Further reading

- [Notes](docs/quickstart/notes.md): first admin, custom domains, SSO,
  shipping updates, and bastion cost
- [Troubleshooting](docs/quickstart/troubleshooting.md): fixes for common
  install errors
- [Manual deploy steps](docs/quickstart/manual-deploy.md): what
  `scripts/deploy-app.sh` does, stage by stage
- [Manual cleanup steps](docs/quickstart/manual-cleanup.md): what
  `scripts/destroy-app.sh` does, step by step
- [`infrastructure/README.md`](infrastructure/README.md): every Terraform
  module and variable
- [`services/README.md`](services/README.md): the scan pipeline architecture
