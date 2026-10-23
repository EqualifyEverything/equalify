# Manual Deploy Steps

`scripts/deploy-app.sh` (Step 6 of the [Quickstart](../../QUICKSTART.md))
automates everything below. This is what it's doing under the hood, if you
want to run a stage by hand, debug a failure, or adapt it into a different
CI pipeline.

## Load the database schema

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

## Apply Hasura metadata

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

## Deploy the scan Lambdas

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

## Deploy the backend API

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

## Deploy the frontend

Populate `apps/frontend/.env.production.local` (not `.env.production` —
that's the real org's committed production config for the existing
GitHub Actions deploy; `.env.production.local` takes precedence in Vite
and is already gitignored, so your own values never touch it) with
`terraform output frontend_env_hints`:

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
