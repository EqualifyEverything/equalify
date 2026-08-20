# Deployment Guide

This guide covers deploying Equalify to AWS infrastructure, including the frontend, backend API, and scanning services.

There are two layers to a deployment:

1. **Infrastructure provisioning** (Terraform) — creates the VPC, RDS instance, the 6 Lambda functions (as no-op stubs), SQS FIFO queues, Hasura (ECS Fargate + ALB), Cognito, and the S3/CloudFront frontend hosting.
2. **Application code deployment** — pushes real Lambda code, the built frontend, the database schema, and Hasura metadata into the infrastructure Terraform provisioned.

For provisioning a brand-new instance from scratch, follow [`QUICKSTART.md`](../../QUICKSTART.md) and [`infrastructure/README.md`](../../infrastructure/README.md) — they walk through both layers end-to-end, including `./scripts/deploy-app.sh` for the "day 2" code/schema deploy. The rest of this guide focuses on the application-code deployment layer (layer 2), which is also what the project's CI workflows run on every push.

## Prerequisites

- AWS CLI v2 with SSO configured
- Node.js 22.x and npm
- AWS account with appropriate permissions
- Java 17+ and Maven (for the PDF scanner's veraPDF Lambda)
- Terraform >= 1.5 (only needed for provisioning new infrastructure, not for redeploying app code to an existing instance)

## AWS SSO Setup

Configure AWS CLI for SSO access (used for manual/local deploys against an existing instance):

```bash
aws configure sso --profile equalifyuic
```

You'll be prompted for:
- **SSO session name**: `equalifyuic-sso`
- **SSO start URL**: `https://equalifyuic.awsapps.com/start`
- **SSO region**: `us-east-2`
- **CLI default region**: `us-east-2`
- **CLI output format**: `json`

To login for subsequent sessions:
```bash
aws sso login --profile equalifyuic
```

Note: CI deploys (`.github/workflows/*.yml`) authenticate via an OIDC IAM role, not this SSO profile — the SSO profile is for manual/local deploys only.

## Project Setup

Install all dependencies from the repository root:

```bash
cd equalify
npm install
```

## Deploying the Frontend

The frontend deploys to S3 with CloudFront CDN.

### Build and Deploy to Staging
```bash
cd apps/frontend
npm run build:staging
```

This command:
1. Builds with Vite using staging environment variables
2. Syncs to S3 bucket `equalifyuic-web-staging`
3. Invalidates CloudFront distribution

### Build and Deploy to Production
```bash
npm run build:prod
```

This command:
1. Builds with Vite using production environment variables
2. Syncs to S3 bucket `equalifyuic-web`
3. Invalidates CloudFront distribution

### Deploy Both Environments
```bash
npm run build
```
Note: this script currently shells out to `yarn build:staging && yarn build:prod` internally, so it requires Yarn to also be installed even though the rest of the project uses npm. Until that's fixed, prefer running `npm run build:staging` and `npm run build:prod` separately.

## Deploying the Backend API

The backend deploys as a single Lambda function.

### Build and Deploy to Staging
```bash
cd apps/backend
npm run build:staging
```

This command:
1. Bundles with esbuild (excluding AWS SDK)
2. Creates `lambda.zip`
3. Updates Lambda function `equalifyuic-api-staging`

### Build and Deploy to Production
```bash
npm run build:prod
```

Updates Lambda function `equalifyuic-api`.

### Deploy Both Environments
```bash
npm run build
```
Same caveat as the frontend: this internally calls `yarn build:staging && yarn build:prod` and requires Yarn to be installed.

## Deploying Scanning Services

Each scanning service deploys independently. `services/aws-lambda-crawler` is a sixth service alongside the four below, providing optional sitemap-based URL discovery.

### SQS Router
```bash
cd services/aws-lambda-scan-sqs-router
npm run build
```

### HTML Scanner
```bash
cd services/aws-lambda-scan-html
npm run build
```

### PDF Scanner (TypeScript)
```bash
cd services/aws-lambda-scan-pdf
npm run build
```

### PDF Scanner (Java/veraPDF)
```bash
cd services/aws-lambda-verapdf-interface
mvn package
# Deploy the resulting JAR to Lambda
```

### Crawler
```bash
cd services/aws-lambda-crawler
npm run build
```

## Environment Configuration

### Frontend Environment Variables

Create `.env.production` and `.env.staging` files (or `.env.production.local` for a local override):

```env
VITE_USERPOOLID=us-east-2_XXXXXXXX
VITE_USERPOOLWEBCLIENTID=XXXXXXXXXXXXXXXXXXXXXXXXXX
VITE_API_URL=https://api.equalifyapp.com
VITE_GRAPHQL_URL=https://graphql.equalifyapp.com
VITE_GRAPHQL_WSS=wss://graphql.equalifyapp.com/v1/graphql
VITE_SSO_ENABLED=true
VITE_SSO_CLIENT_ID=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
VITE_SSO_AUTHORITY=https://login.microsoftonline.com/TENANT_ID
VITE_APP_DOMAIN=equalifyapp.com
VITE_TELEMETRY_ENABLED=true
VITE_TELEMETRY_ENDPOINT=https://telemetry.equalifyapp.com
VITE_BRANCH=production
```

### Backend Environment Variables

Configure in AWS Lambda:

| Variable | Description |
|----------|-------------|
| `DB_USER` | PostgreSQL username |
| `DB_HOST` | PostgreSQL host (RDS endpoint) |
| `DB_NAME` | Database name |
| `DB_PASSWORD` | Database password / Hasura admin secret |
| `USER_POOL_ID` | Cognito User Pool ID |
| `WEB_CLIENT_ID` | Cognito Web Client ID |
| `SSO_ENABLED` | "true" to enable SSO |
| `SSO_CLIENT_ID` | Azure AD SSO client ID |
| `SSO_TENANT` | Azure AD tenant ID |
| `SSO_JWKS` | Azure AD JWKS endpoint for SSO token verification |
| `WEBHOOKSECRET` | Hasura webhook secret |
| `GRAPHQL_URL` | Hasura GraphQL endpoint |
| `SQS_ROUTER_FUNCTION_NAME` | Function name of the SQS Router Lambda |
| `CRAWLER_FUNCTION_NAME` | Function name of the crawler Lambda |

### Scanner Environment Variables

| Variable | Description |
|----------|-------------|
| `SCAN_WEBHOOK_URL` | Webhook URL for production results (falls back to a hardcoded default if unset) |
| `SCAN_WEBHOOK_URL_STAGING` | Webhook URL for staging results |
| `VERAPDF_FUNCTION_NAME` | Function name of the veraPDF interface Lambda |
| `SQS_HTML_QUEUE_URL` / `SQS_PDF_QUEUE_URL` | Queue URLs the SQS Router sends batches to |

## AWS Infrastructure

Infrastructure is provisioned via Terraform (`infrastructure/`) — see the module layout in [`infrastructure/README.md`](../../infrastructure/README.md) for full detail. Key resources:

- **Lambda Functions** (6 total): API, SQS Router, Crawler, HTML Scanner, PDF Scanner, veraPDF interface
- **SQS Queues**: `scanHtml.fifo` and `scanPdf.fifo`, project/environment-prefixed in Terraform-provisioned deployments (e.g. `<project>-<environment>-scanHtml.fifo`), each with its own dead-letter queue (14-day retention)
- **S3 Buckets**: Frontend hosting (staging + production)
- **CloudFront**: CDN distributions
- **RDS**: PostgreSQL instance (private subnet, no public IP)
- **Cognito**: User Pool for authentication
- **Hasura**: GraphQL engine, deployed as ECS Fargate behind an ALB by the Terraform module

### Lambda Layers

The HTML scanner requires a Chromium layer. Use a pre-built layer:
- `@sparticuz/chromium` compatible layer

### SQS Configuration

FIFO queues with:
- Content-based deduplication: **Enabled**
- Message deduplication ID: `${scanId}-${urlId}` (set explicitly by the SQS Router in addition to content-based dedup)
- Message group ID: `auditId` for ordered processing
- Visibility timeout: 5 minutes (for HTML scanner)

## Monitoring

### CloudWatch Logs

All Lambda functions log to CloudWatch. Log group naming depends on which deployment path provisioned the function:

- The existing hosted instance (deployed via `.github/workflows/*.yml`) uses these names:
  - `/aws/lambda/equalifyuic-api`
  - `/aws/lambda/equalifyuic-api-staging`
  - `/aws/lambda/aws-lambda-scan-sqs-router`
  - `/aws/lambda/aws-lambda-scan-html`
  - `/aws/lambda/aws-lambda-scan-pdf`
  - `/aws/lambda/aws-lambda-crawler`
- A fresh Terraform-provisioned instance names log groups after the function names Terraform creates (project/environment-prefixed) — check `terraform output lambda_function_names` for the exact names.

### Lambda Powertools Metrics

Scanning services emit metrics:
- `scansStarted` - Count (HTML/PDF scanner Lambdas)
- `ScanDuration` - Milliseconds
- `scanRequest` - Count (SQS Router)

### Health Checks

Monitor:
- Lambda error rates
- SQS queue depth (and dead-letter queue depth)
- Database connections
- CloudFront error rates
- Hasura/ECS service health (for Terraform-provisioned instances)

## Troubleshooting

### Common Issues

**SSO Token Expired**
```bash
aws sso login --profile equalifyuic
```

**Lambda Deployment Fails**
- Ensure AWS CLI is authenticated
- Check Lambda function exists
- Verify IAM permissions

**Frontend Build Errors**
- Clear `node_modules` and reinstall
- Check environment variables are set

**Scan Timeouts**
- Increase Lambda timeout
- Check if pages are very large/slow
- Review CloudWatch logs for errors

### Logs

View recent logs:
```bash
aws logs tail /aws/lambda/equalifyuic-api --follow --profile equalifyuic
```

---
*For architecture details, see the Architecture Overview.*
