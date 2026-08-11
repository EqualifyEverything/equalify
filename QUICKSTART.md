# Quickstart: Installing Your Own Instance of Equalify

This guide walks through provisioning a complete, self-contained instance of
Equalify on your own AWS account: database, scan Lambdas, GraphQL API
(Hasura), backend API, and the web frontend.

Equalify's infrastructure is defined entirely in Terraform
(`infrastructure/`), which provisions empty/stubbed resources. This guide
covers both that provisioning step and the "day 2" step of deploying real
application code and schema into it.

## Prerequisites

- An AWS account, and credentials/CLI access with sufficient privileges (VPC,
  RDS, ECS, Lambda, S3, CloudFront, Cognito, SQS, Secrets Manager, IAM, and
  Route53/ACM if you're using a custom domain)
- [Terraform](https://developer.hashicorp.com/terraform/install) >= 1.5 —
  note for macOS/Homebrew users: the core `terraform` formula was removed
  after HashiCorp's license change, so install via
  `brew tap hashicorp/tap && brew install hashicorp/tap/terraform`
  (or use [OpenTofu](https://opentofu.org/))
- [AWS CLI v2](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
- Node.js 22.x and npm
- Java 17 and Maven (only needed to build the PDF-validation Lambda)
- `psql` / `pg_dump` (any recent PostgreSQL client)
- The [Session Manager plugin](https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html)
  for the AWS CLI — RDS is provisioned with no public IP, so schema/migration
  access tunnels through a small SSM-only bastion instance (see Step 6)
- A verified [SES](https://docs.aws.amazon.com/ses/latest/dg/verify-addresses-and-domains.html)
  identity (domain or single address) in the AWS account/region you're
  deploying to — required for invite emails and scan summaries to send

## 1. Clone the repo and install dependencies

```
git clone https://github.com/equalifyEverything/equalify.git
cd equalify
npm install
```

## 2. Bootstrap Terraform remote state

Terraform needs an S3 bucket + DynamoDB table to store its state before it
can provision anything else.

```
cd infrastructure/bootstrap
terraform init
terraform apply
terraform output
```

Note the `state_bucket_name`, `lock_table_name`, and `aws_region` outputs —
you'll need them in the next step.

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

Edit `terraform.tfvars` and fill in, at minimum:

- `cognito_domain_prefix` — must be globally unique (becomes
  `<prefix>.auth.<region>.amazoncognito.com`)
- `ses_admin_email` — must match the SES identity you verified above

Everything else has a working default. See the comments in
`terraform.tfvars.example` for optional knobs: a custom domain
(`domain_name` + `route53_zone_id`), instance sizing, Azure AD SSO, and
CloudWatch alarm email.

## 5. Provision the infrastructure

```
terraform plan
terraform apply
terraform output
```

This single `apply` provisions one full environment: RDS (PostgreSQL), the 6
Lambda functions, SQS FIFO queues, Hasura (ECS Fargate + ALB), Cognito, S3 +
CloudFront for the frontend, and a small SSM-only bastion instance (no SSH
key, no public IP) for the DB access in the next step — each Lambda is
created with a no-op stub and the frontend bucket is empty. The remaining
steps deploy real code and schema into what was just created.

Keep this terminal's `terraform output` handy — later steps reference values
from it (`rds_endpoint`, `graphql_url`, `lambda_function_names`, etc).

## 6. Deploy app code, schema, and Hasura metadata

Everything past this point — loading `db/schema.sql` + `db/migrations/*.sql`,
applying `db/hasura-metadata.json`, building/pushing all 6 Lambdas, and
building/publishing the frontend — is boilerplate that can be read straight
out of `terraform output`. `scripts/deploy-app.sh` does exactly that:

```
./scripts/deploy-app.sh
```

Requires `terraform`, `aws`, `jq`, `npm`, `psql`, the Session Manager plugin,
and `mvn` (Java/Maven, for the PDF-validation Lambda) in `PATH`, and AWS
credentials active in your shell (same ones Terraform used). It reads every
target — function names, S3 bucket, RDS endpoint, secret ARNs, Cognito IDs —
from `terraform output`, so nothing in the script is hardcoded to a
particular AWS account.

For the schema/migration step, RDS has no public IP by design, so the
script starts the bastion instance Terraform provisioned, opens an SSM
port-forward tunnel through it to RDS, runs `psql` against `localhost`, then
tears the tunnel down and stops the bastion again afterward to keep it from
running (and costing anything) between uses.

Run it with `--help` to see options, including skipping individual stages
on reruns (`--skip-db`, `--skip-hasura`, `--skip-lambdas`, `--skip-frontend`)
and creating your first user in the same pass (`--create-user
you@example.com`) — it prints a permanent password to the terminal rather
than emailing one, so the very first login doesn't depend on any email
deliverability (see Step 7).

If you'd rather run each stage by hand (e.g. to understand what it's doing,
or to adapt it for a CI pipeline), see [Manual deploy steps](#manual-deploy-steps)
below.

## 7. Log in and run your first scan

1. Create your first user with `./scripts/deploy-app.sh --skip-db
   --skip-hasura --skip-lambdas --skip-frontend --create-user
   you@example.com` (skip whichever stages you don't need to rerun). It
   prints an email + password directly to the terminal — save it, it isn't
   shown again. This account automatically becomes the first admin (see
   Notes), which is what unlocks the admin-only routes (reviewing access
   requests, etc.).

   This deliberately doesn't rely on Cognito's own account-verification
   email. That email works out of the box with no SES setup at all (it's
   entirely separate from the app's own SES config below — Cognito has its
   own built-in sender for account verification), but a fresh install
   shouldn't have its very first login gated on any email actually arriving.
2. Visit `terraform output frontend_url` and sign in with that email/password.
3. Submit a URL to scan. Scans are routed through
   `aws-lambda-scan-sqs-router` → the HTML or PDF scan queue → results post
   back to the backend's webhook (see `services/README.md` for the full scan
   pipeline diagram).

## Cleanup (avoid ongoing AWS charges)

This tears down every resource provisioned in this guide — RDS, the Hasura
ECS service + ALB, NAT gateway, all 6 Lambdas, SQS, Cognito, S3 + CloudFront,
and Secrets Manager entries, which together are the bulk of an idle stack's
hourly cost. **This permanently deletes all scan data, audits, and users —
only do this against a disposable test/eval stack.**

```
./scripts/destroy-app.sh
```

It empties the frontend S3 bucket, disables RDS deletion protection (which
defaults to `true` specifically to block an accidental destroy), runs
`terraform destroy`, then force-deletes the Secrets Manager entries so they
don't sit in their 7-day recovery window still costing ~$0.40/month each.
It asks for a typed confirmation before deleting anything — pass `--yes` to
skip that (and auto-approve `terraform destroy`) for scripted/CI use, and
`--skip-final-snapshot` to skip RDS's final snapshot if the data is truly
disposable.

The bootstrap remote-state stack (S3 state bucket + DynamoDB lock table)
costs a few cents a month at most, so it's left in place by default — pass
`--destroy-bootstrap` to remove that too (asks for its own separate
confirmation, since it also deletes your Terraform state).

See [Manual cleanup steps](#manual-cleanup-steps) below for what it's doing
under the hood, if you'd rather run it by hand or adapt it.

## Notes

- **First admin bootstrap**: the very first user to complete sign-in
  automatically becomes an admin (`users.type = 'admin'`) — a Cognito
  PostConfirmation trigger checks whether the `users` table is empty and, if
  so, inserts the new user as admin instead of the default `member`. This
  only fires once, on that first confirmation; every subsequent signup
  lands as a regular member. If you ever need to promote someone else
  later, that's a direct `UPDATE users SET type='admin' WHERE email='...'`
  (through the bastion tunnel — see Step 6) — there's no UI for it yet.
- **Custom domain**: if you set `domain_name` + `route53_zone_id` in
  `terraform.tfvars`, Terraform provisions ACM certs and Route53 records for
  `app./api./graphql.<domain>` automatically — the outputs above will
  reflect those instead of AWS-issued default endpoints.
- **SSO**: if `sso_enabled = true`, fill in real Azure AD tenant config in
  the `<project>/<environment>/sso-config` Secrets Manager entry after the
  first apply, then re-apply.
- **Updating later**: re-running `./scripts/deploy-app.sh` (rebuild + push)
  is how you ship code updates; re-running `terraform apply` after changing
  `.tf`/`.tfvars` is how you change infrastructure. Terraform intentionally
  never overwrites Lambda code after the first apply (see
  `infrastructure/README.md`'s "Artifact handoff" section), so the two
  update paths don't conflict.
- **Bastion cost**: the SSM bastion (`bastion_instance_type`, default
  `t3.micro`) is only needed while `scripts/deploy-app.sh` is actively
  loading schema/migrations — it stops itself again afterward, so it should
  normally cost close to nothing (a few cents/month of EBS storage while
  stopped). If you ever find it left running, `aws ec2 stop-instances
  --instance-ids <bastion_instance_id>` is safe at any time; `terraform
  destroy` removes it entirely along with everything else.
- Full detail on every module and variable lives in
  `infrastructure/README.md`; the scan pipeline architecture is documented
  in `services/README.md`.

## Troubleshooting

### `terraform apply` fails with "No valid credential sources found"

Full error looks like:

```
Error: No valid credential sources found
...
Error: failed to refresh cached credentials, no EC2 IMDS role found, operation error ec2imds: GetMetadata, request canceled, context deadline exceeded
```

Terraform's AWS provider fell through its entire credential chain
(environment variables, the shared config/credentials file, ECS/EC2 instance
role) and, as a last resort, tried to reach the EC2 instance metadata
service — which isn't reachable outside an EC2 instance, hence the
multi-second timeout before it finally fails.

This means Terraform can't see the same credentials your `aws` CLI is using.
Run `aws configure list` first to see *why* — the `TYPE` column tells you
which of the two common causes below you're hitting.

```
$ aws configure list
NAME       : VALUE                    : TYPE             : LOCATION
profile    : <not set>                : None             : None
access_key : ****************4LZ7     : login            :
secret_key : ****************BTx5     : login            :
region     : us-east-2                : config-file      : ~/.aws/config
```

**If `TYPE` is `sso` or `shared-credentials-file`**, you're on a named
profile (e.g. via `aws configure sso --profile equalifyuic`, per
`CONTRIBUTE.md`'s SSO setup). Your `aws` CLI commands worked because you
passed `--profile <name>` explicitly (or it's set elsewhere) — but neither
`terraform apply` nor `scripts/deploy-app.sh`/`destroy-app.sh` know to use
that profile unless it's exported as `AWS_PROFILE`:

```
export AWS_PROFILE=<your-profile>  # e.g. equalifyuic
aws sts get-caller-identity        # confirm this profile has live credentials
terraform apply                    # re-run in the same shell
```

If `aws sts get-caller-identity` itself fails, the cached SSO token expired —
re-authenticate with `aws sso login --profile <your-profile>` first.

**If `TYPE` is `login`** (as in the example above), your credentials came
from the AWS CLI's newer browser-based `aws login` flow. Those are cached
internally by the CLI — not written to `~/.aws/credentials` as a profile,
and not exported as env vars — so no *other* tool built on the AWS SDK
(Terraform, boto3, any language's SDK) can see them, regardless of profile
settings. Bridge them into your shell with the CLI's own export command:

```
eval "$(aws configure export-credentials --format env)"
terraform apply
```

These are typically short-lived session credentials, so if you hit
credential errors again later (mid-`terraform apply`, or during
`scripts/deploy-app.sh`/`destroy-app.sh`), re-run `aws login` followed by
the `eval` line above to refresh them.

Either way: the credentials need to be visible in the *exact shell process*
running `terraform`/the scripts — a new terminal tab, a new SSH session, or
running via `sudo` all start clean and won't inherit anything you exported
earlier elsewhere.

## Manual deploy steps

`scripts/deploy-app.sh` automates everything below. This is what it's doing
under the hood, if you want to run a stage by hand, debug a failure, or
adapt it into a different CI pipeline.

### Load the database schema

RDS is provisioned with `publicly_accessible = false` and a security group
that only allows Postgres from the backend Lambda, the Hasura task, and the
bastion instance (`terraform output bastion_instance_id`) — there's no path
to it from your laptop directly, by design. Open an SSM port-forward tunnel
through the bastion first:

```
aws ssm start-session --target <bastion_instance_id from output> \
  --document-name AWS-StartPortForwardingSessionToRemoteHost \
  --parameters '{"host":["<db host, from rds_endpoint>"],"portNumber":["<db port, from rds_endpoint>"],"localPortNumber":["15432"]}'
```

(If the bastion is stopped, `aws ec2 start-instances --instance-ids
<bastion_instance_id>` first and wait for it to reach `running`.) Leave that
command running in its own terminal, then in another terminal, using the
`db_name`/`db_username` outputs and the DB password (in Secrets Manager —
`terraform output db_password_secret_arn`):

```
aws secretsmanager get-secret-value --secret-id <db_password_secret_arn> --query SecretString --output text

PGPASSWORD=<password> psql -h 127.0.0.1 -p 15432 \
  -U <db_username> -d <db_name> -f db/schema.sql

for f in db/migrations/*.sql; do
  PGPASSWORD=<password> psql -h 127.0.0.1 -p 15432 -U <db_username> -d <db_name> -f "$f"
done
```

`db/schema.sql` includes the `pgcrypto` extension it depends on. Once done,
stop the tunnel (Ctrl-C in its terminal) and, to avoid it costing anything
between uses, stop the bastion: `aws ec2 stop-instances --instance-ids
<bastion_instance_id>`.

### Apply Hasura metadata

Fetch the Hasura admin secret and replace metadata from
`db/hasura-metadata.json` via Hasura's metadata API directly (the same
approach `db/dump.sh` uses to export it):

```
aws secretsmanager get-secret-value --secret-id <hasura_admin_secret_arn from output> --query SecretString --output text

curl -X POST "<graphql_url from output, without /v1/graphql>/v1/metadata" \
  -H "X-Hasura-Admin-Secret: <hasura admin secret>" \
  -H "Content-Type: application/json" \
  -d "{\"type\":\"replace_metadata\",\"version\":2,\"args\":{\"metadata\": $(jq '.metadata' db/hasura-metadata.json), \"allow_inconsistent_metadata\": true}}"
```

### Deploy the scan Lambdas

Each Lambda in `services/` builds independently. Build, zip, and push each
one to the function name Terraform created (`terraform output
lambda_function_names`):

```
for svc in aws-lambda-scan-sqs-router aws-lambda-scan-html aws-lambda-scan-pdf aws-lambda-crawler; do
  cd services/$svc
  npm install
  npm run dist
  cd dist && zip -r lambda.zip lambda.* > /dev/null
  aws lambda update-function-code \
    --function-name <matching name from lambda_function_names output> \
    --zip-file fileb://lambda.zip
  cd ../../..
done
```

The PDF validator (`aws-lambda-verapdf-interface`) is a Java/Maven build:

```
cd services/aws-lambda-verapdf-interface
mvn clean package
aws lambda update-function-code \
  --function-name <verapdf_interface name from lambda_function_names output> \
  --zip-file fileb://target/aws-lambda-verapdf-interface-1.0-SNAPSHOT.jar
cd ../..
```

### Deploy the backend API

```
cd apps/backend
npm install
npx esbuild index.ts --bundle --platform=node --outdir=dist --external:@aws-sdk --loader:.node=file
cd dist && zip -r lambda.zip index.js > /dev/null
aws lambda update-function-code \
  --function-name <backend name from lambda_function_names output> \
  --zip-file fileb://lambda.zip
cd ../../..
```

Terraform already configured this Lambda's environment variables (DB
credentials, Hasura/Cognito/SES config, etc.) — no `.env` file needed here.

### Deploy the frontend

Populate `apps/frontend/.env.production` with `terraform output
frontend_env_hints`:

```
VITE_API_URL=<api_url>
VITE_GRAPHQL_URL=<graphql_url>
VITE_GRAPHQL_WSS=<graphql_wss_url>
VITE_USERPOOLID=<cognito_user_pool_id>
VITE_USERPOOLWEBCLIENTID=<cognito_web_client_id>
```

Then build and publish:

```
cd apps/frontend
npm install
npx vite build --mode production
aws s3 sync --delete ./dist s3://<frontend_bucket_name from output>
aws cloudfront create-invalidation \
  --distribution-id <frontend_cloudfront_distribution_id from output> \
  --paths "/*"
```

## Manual cleanup steps

`scripts/destroy-app.sh` automates everything below.

1. Empty the frontend S3 bucket. Terraform won't delete a non-empty bucket,
   and this bucket isn't versioned so a plain recursive delete is enough:

   ```
   cd infrastructure
   aws s3 rm "s3://$(terraform output -raw frontend_bucket_name)" --recursive
   ```

2. Disable RDS deletion protection. It defaults to `true`
   (`db_deletion_protection` in tfvars) specifically to prevent an accidental
   `terraform destroy` from taking out the database, so destroy will fail on
   it until this is turned off:

   ```
   aws rds modify-db-instance \
     --db-instance-identifier "$(terraform output -raw rds_instance_id)" \
     --no-deletion-protection --apply-immediately

   # wait for it to leave "modifying" before continuing
   aws rds describe-db-instances \
     --db-instance-identifier "$(terraform output -raw rds_instance_id)" \
     --query 'DBInstances[0].DBInstanceStatus'
   ```

3. Note the secret ARNs before destroying — `terraform output` won't work
   once the state is gone, and you'll want these in the next step:

   ```
   SECRET_ARNS="$(terraform output -json | jq -r '[.db_password_secret_arn.value, .hasura_admin_secret_arn.value, .webhook_secret_arn.value, .sso_config_secret_arn.value] | map(select(. != null)) | join(" ")')"
   ```

4. Destroy the stack:

   ```
   terraform destroy
   ```

   Terraform will prompt for confirmation before deleting anything. By
   default this also takes a final RDS snapshot (a small ongoing storage
   cost) — add `-var db_skip_final_snapshot=true` to skip it if the data is
   truly disposable.

5. Force-delete the secrets. `terraform destroy` only *schedules* Secrets
   Manager entries for deletion after a 7-day recovery window, during which
   they keep costing ~$0.40/month each:

   ```
   for arn in $SECRET_ARNS; do
     aws secretsmanager delete-secret --secret-id "$arn" --force-delete-without-recovery
   done
   ```

6. Optional — tear down the bootstrap remote-state stack (the S3 state
   bucket + DynamoDB lock table from step 2 of the install). Its ongoing
   cost is a few cents at most, so most people leave it, but to remove it
   fully:

   ```
   cd bootstrap
   BUCKET="$(terraform output -raw state_bucket_name)"

   # aws_s3_bucket.terraform_state has prevent_destroy = true, so it can't
   # go through `terraform destroy`. Untrack it (this does NOT touch the
   # real bucket) so destroy can remove everything else:
   terraform state rm \
     aws_s3_bucket.terraform_state \
     aws_s3_bucket_versioning.terraform_state \
     aws_s3_bucket_server_side_encryption_configuration.terraform_state \
     aws_s3_bucket_public_access_block.terraform_state

   terraform destroy

   # the state bucket is versioned, so every version + delete marker
   # needs to be removed individually before it can be deleted
   aws s3api list-object-versions --bucket "$BUCKET" --output json \
     | jq -r '(.Versions // [])[], (.DeleteMarkers // [])[] | "\(.Key)\t\(.VersionId)"' \
     | while IFS=$'\t' read -r key vid; do
         aws s3api delete-object --bucket "$BUCKET" --key "$key" --version-id "$vid"
       done

   aws s3api delete-bucket --bucket "$BUCKET"
   ```
