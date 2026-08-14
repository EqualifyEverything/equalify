#!/usr/bin/env bash
#
# Deploys real application code, database schema, and Hasura metadata into
# the AWS infrastructure Terraform already provisioned (infrastructure/).
# Run this after `terraform apply` — see QUICKSTART.md.
#
# Reads every target (function names, S3 bucket, RDS endpoint, secrets, ...)
# straight from `terraform output`, so nothing here is hardcoded to a
# particular AWS account or environment.
#
# Usage: scripts/deploy-app.sh [--skip-db] [--skip-hasura] [--skip-lambdas] [--skip-frontend] [--create-user EMAIL]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
INFRA_DIR="$REPO_ROOT/infrastructure"

SKIP_DB=0
SKIP_HASURA=0
SKIP_LAMBDAS=0
SKIP_FRONTEND=0
CREATE_USER_EMAIL=""

usage() {
  cat <<EOF
Usage: $0 [options]

Deploys application code/schema into infrastructure Terraform already
provisioned. Run 'terraform apply' in infrastructure/ first.

Options:
  --skip-db          Skip loading db/schema.sql + db/migrations/*.sql
  --skip-hasura       Skip applying db/hasura-metadata.json
  --skip-lambdas      Skip building/pushing the 6 Lambda functions
  --skip-frontend     Skip building/publishing the frontend
  --create-user EMAIL Create a Cognito user with this email after deploying
  -h, --help          Show this help
EOF
}

while [ $# -gt 0 ]; do
  case "$1" in
    --skip-db) SKIP_DB=1; shift ;;
    --skip-hasura) SKIP_HASURA=1; shift ;;
    --skip-lambdas) SKIP_LAMBDAS=1; shift ;;
    --skip-frontend) SKIP_FRONTEND=1; shift ;;
    --create-user) CREATE_USER_EMAIL="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage; exit 1 ;;
  esac
done

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
die() { echo "Error: $1" >&2; exit 1; }

require() {
  command -v "$1" >/dev/null 2>&1 || die "'$1' is required but not found in PATH. $2"
}

require terraform "Install: https://developer.hashicorp.com/terraform/install"
require aws "Install: https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html"
require jq "Install: apt install jq / brew install jq"
require npm "Install Node.js 22.x: https://nodejs.org"
if [ "$SKIP_DB" = 0 ]; then
  require psql "Install any recent PostgreSQL client, or pass --skip-db."
  require session-manager-plugin "Install: https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html"
fi
if [ "$SKIP_LAMBDAS" = 0 ]; then
  require mvn "Needed to build aws-lambda-verapdf-interface, or pass --skip-lambdas."
  require zip "Install: apt install zip / brew install zip"
fi
[ -z "$CREATE_USER_EMAIL" ] || require openssl "Needed to generate the new user's password."

log "Reading Terraform outputs"
cd "$INFRA_DIR"
TF_OUT="$(terraform output -json)"
cd "$REPO_ROOT"

[ "$(echo "$TF_OUT" | jq -r '.lambda_function_names // empty')" != "" ] \
  || die "No Terraform outputs found. Run 'terraform apply' in infrastructure/ first."

out() { echo "$TF_OUT" | jq -r "$1"; }

RDS_ENDPOINT="$(out '.rds_endpoint.value')"
DB_HOST="${RDS_ENDPOINT%%:*}"
DB_PORT="${RDS_ENDPOINT##*:}"
DB_NAME="$(out '.db_name.value')"
DB_USERNAME="$(out '.db_username.value')"
DB_PASSWORD_SECRET_ARN="$(out '.db_password_secret_arn.value')"
BASTION_ID="$(out '.bastion_instance_id.value')"
DEPLOY_ARTIFACTS_BUCKET="$(out '.deploy_artifacts_bucket_name.value')"
HASURA_ADMIN_SECRET_ARN="$(out '.hasura_admin_secret_arn.value')"
GRAPHQL_URL="$(out '.graphql_url.value')"
GRAPHQL_BASE_URL="${GRAPHQL_URL%/v1/graphql}"
FRONTEND_BUCKET="$(out '.frontend_bucket_name.value')"
FRONTEND_DIST_ID="$(out '.frontend_cloudfront_distribution_id.value')"
FRONTEND_URL="$(out '.frontend_url.value')"
COGNITO_USER_POOL_ID="$(out '.cognito_user_pool_id.value')"

fn_name() { out ".lambda_function_names.value.$1"; }

# ---------------------------------------------------------------------------
if [ "$SKIP_DB" = 0 ]; then
  log "Loading database schema (db/schema.sql + db/migrations/*.sql)"

  # RDS is deliberately private (no public IP, security group only allows the
  # backend Lambda / Hasura task / this bastion) — tunnel through the SSM
  # bastion rather than connecting to $DB_HOST directly.
  LOCAL_PORT=15432
  STARTED_BASTION=0
  TUNNEL_PID=""

  cleanup_tunnel() {
    [ -n "$TUNNEL_PID" ] && kill "$TUNNEL_PID" >/dev/null 2>&1 || true
    [ -n "$TUNNEL_PID" ] && wait "$TUNNEL_PID" 2>/dev/null || true
    if [ "$STARTED_BASTION" = 1 ]; then
      echo "Stopping bastion instance ($BASTION_ID)..."
      aws ec2 stop-instances --instance-ids "$BASTION_ID" >/dev/null 2>&1 || true
    fi
  }
  trap cleanup_tunnel EXIT

  BASTION_STATE="$(aws ec2 describe-instances --instance-ids "$BASTION_ID" \
    --query 'Reservations[0].Instances[0].State.Name' --output text)"
  if [ "$BASTION_STATE" != "running" ]; then
    echo "Starting bastion instance ($BASTION_ID)..."
    aws ec2 start-instances --instance-ids "$BASTION_ID" >/dev/null
    aws ec2 wait instance-running --instance-ids "$BASTION_ID"
    STARTED_BASTION=1
  fi

  echo "Waiting for the bastion's SSM agent to register..."
  SSM_ONLINE=0
  for _ in $(seq 1 30); do
    PING="$(aws ssm describe-instance-information \
      --filters "Key=InstanceIds,Values=$BASTION_ID" \
      --query 'InstanceInformationList[0].PingStatus' --output text 2>/dev/null || echo "")"
    if [ "$PING" = "Online" ]; then
      SSM_ONLINE=1
      break
    fi
    sleep 5
  done
  [ "$SSM_ONLINE" = 1 ] || die "Bastion SSM agent never came online ($BASTION_ID). Check the instance and try again."

  echo "Opening SSM tunnel: localhost:$LOCAL_PORT -> $DB_HOST:$DB_PORT (via $BASTION_ID)"
  aws ssm start-session --target "$BASTION_ID" \
    --document-name AWS-StartPortForwardingSessionToRemoteHost \
    --parameters "{\"host\":[\"$DB_HOST\"],\"portNumber\":[\"$DB_PORT\"],\"localPortNumber\":[\"$LOCAL_PORT\"]}" \
    >/tmp/equalify-ssm-tunnel.log 2>&1 &
  TUNNEL_PID=$!

  echo "Waiting for tunnel to accept connections..."
  TUNNEL_READY=0
  for _ in $(seq 1 30); do
    if (exec 3<>"/dev/tcp/127.0.0.1/$LOCAL_PORT") 2>/dev/null; then
      TUNNEL_READY=1
      break
    fi
    sleep 1
  done
  [ "$TUNNEL_READY" = 1 ] || die "SSM tunnel never became ready. See /tmp/equalify-ssm-tunnel.log."

  DB_PASSWORD="$(aws secretsmanager get-secret-value --secret-id "$DB_PASSWORD_SECRET_ARN" --query SecretString --output text)"
  export PGPASSWORD="$DB_PASSWORD"

  ALREADY_LOADED="$(psql -h 127.0.0.1 -p "$LOCAL_PORT" -U "$DB_USERNAME" -d "$DB_NAME" -tAc \
    "select 1 from information_schema.tables where table_schema='public' limit 1" 2>/dev/null || echo "")"

  if [ "$ALREADY_LOADED" = "1" ]; then
    echo "Schema already present — skipping db/schema.sql (pass --skip-db to silence this check)."
  else
    # --single-transaction: if schema.sql fails partway, nothing commits, so
    # a rerun's ALREADY_LOADED check above can't see a half-applied schema
    # and mistake it for "already loaded" (the failure mode that bit an
    # earlier version of this script).
    psql -h 127.0.0.1 -p "$LOCAL_PORT" -U "$DB_USERNAME" -d "$DB_NAME" --single-transaction -v ON_ERROR_STOP=1 -f db/schema.sql
  fi

  for f in db/migrations/*.sql; do
    [ -e "$f" ] || continue
    echo "Applying migration: $f"
    psql -h 127.0.0.1 -p "$LOCAL_PORT" -U "$DB_USERNAME" -d "$DB_NAME" --single-transaction -v ON_ERROR_STOP=1 -f "$f"
  done
  unset PGPASSWORD

  cleanup_tunnel
  trap - EXIT
else
  log "Skipping database schema (--skip-db)"
fi

# ---------------------------------------------------------------------------
if [ "$SKIP_HASURA" = 0 ]; then
  log "Applying Hasura metadata (db/hasura-metadata.json) to $GRAPHQL_BASE_URL"
  HASURA_ADMIN_SECRET="$(aws secretsmanager get-secret-value --secret-id "$HASURA_ADMIN_SECRET_ARN" --query SecretString --output text)"

  METADATA_ARGS="$(jq -c '{metadata: .metadata, allow_inconsistent_metadata: true}' db/hasura-metadata.json)"
  RESPONSE="$(curl -sf -X POST "$GRAPHQL_BASE_URL/v1/metadata" \
    -H "X-Hasura-Admin-Secret: $HASURA_ADMIN_SECRET" \
    -H "Content-Type: application/json" \
    -d "{\"type\":\"replace_metadata\",\"version\":2,\"args\":$METADATA_ARGS}")"

  # With allow_inconsistent_metadata:true (set above so one bad object
  # doesn't hard-fail the whole apply), a successful call returns
  # {"is_consistent": ..., "inconsistent_objects": [...]} instead of the
  # plain {"message":"success"} it'd return without that flag. Only an
  # actual API error has an "error"/"code" field, so check for that instead
  # of requiring one specific success shape.
  echo "$RESPONSE" | jq -e '(.error == null) and (.code == null)' >/dev/null \
    || die "Hasura metadata apply failed: $RESPONSE"

  if echo "$RESPONSE" | jq -e '.is_consistent == false' >/dev/null 2>&1; then
    echo "Warning: metadata applied but reported inconsistent objects:"
    echo "$RESPONSE" | jq '.inconsistent_objects'
  fi

  # Hasura only re-introspects tracked tables' actual Postgres columns on a
  # metadata reload — not automatically, and not just because the ECS task
  # restarted. Without this, columns added by db/migrations/*.sql (which
  # just ran, in the DB step above) stay invisible to GraphQL indefinitely
  # even though they're really in the database.
  log "Reloading Hasura metadata (picks up any schema/migration changes)"
  RELOAD_RESPONSE="$(curl -sf -X POST "$GRAPHQL_BASE_URL/v1/metadata" \
    -H "X-Hasura-Admin-Secret: $HASURA_ADMIN_SECRET" \
    -H "Content-Type: application/json" \
    -d '{"type":"reload_metadata","args":{"reload_remote_schemas":false}}')"

  echo "$RELOAD_RESPONSE" | jq -e '(.error == null) and (.code == null)' >/dev/null \
    || die "Hasura metadata reload failed: $RELOAD_RESPONSE"
else
  log "Skipping Hasura metadata (--skip-hasura)"
fi

# ---------------------------------------------------------------------------
if [ "$SKIP_LAMBDAS" = 0 ]; then
  deploy_node_lambda() {
    local svc_dir="$1" fn="$2"
    log "Building & deploying $svc_dir -> $fn"
    (
      cd "services/$svc_dir"
      npm install
      npm run dist

      # @axe-core/puppeteer locates axe-core via a runtime require.resolve()
      # + fs.readFileSync (it injects axe's actual source into the browser
      # page) — a dynamic filesystem lookup esbuild can't inline away like a
      # normal import, so the real package files must ship alongside the
      # bundled code. Resolve via Node itself rather than assuming a local
      # node_modules/axe-core path: this is an npm workspaces monorepo, so
      # the package is often hoisted to the repo root instead. No-op for
      # services (scan-pdf, scan-sqs-router, crawler) that don't depend on
      # axe-core at all.
      AXE_CORE_DIR="$(node -e "try { console.log(require.resolve('axe-core/package.json').replace(/[\\/\\\\]package\.json$/, '')) } catch (e) {}")"
      if [ -n "$AXE_CORE_DIR" ] && [ -d "$AXE_CORE_DIR" ]; then
        mkdir -p dist/node_modules
        cp -r "$AXE_CORE_DIR" dist/node_modules/axe-core
      fi

      cd dist
      rm -f lambda.zip
      zip -rq lambda.zip lambda.* $( [ -d node_modules ] && echo node_modules )
      aws lambda update-function-code --function-name "$fn" --zip-file "fileb://lambda.zip" >/dev/null
      rm -f lambda.zip
    )
  }

  deploy_node_lambda "aws-lambda-scan-sqs-router" "$(fn_name scan_sqs_router)"
  deploy_node_lambda "aws-lambda-scan-html" "$(fn_name scan_html)"
  deploy_node_lambda "aws-lambda-scan-pdf" "$(fn_name scan_pdf)"
  deploy_node_lambda "aws-lambda-crawler" "$(fn_name crawler)"

  # aws-lambda-scan-html uses @sparticuz/chromium-min, which expects the
  # actual (large, brotli-compressed) Chromium binary to already be present
  # at /opt/nodejs/node_modules/@sparticuz/chromium/bin — i.e. provided by a
  # Lambda layer, not bundled in the function's own zip. Skip rebuilding if a
  # layer with the expected name is already attached (it's a slow ~50MB
  # npm-install-and-zip, not worth repeating on every deploy).
  SCAN_HTML_FN="$(fn_name scan_html)"
  CHROMIUM_LAYER_NAME="${SCAN_HTML_FN}-chromium"
  # jq against raw JSON, not --query/--output text: when Layers is absent
  # (null) on a fresh function, --query "Layers[?...]" --output text prints
  # the literal string "None" — non-empty, so a naive [ -n ... ] check
  # thinks a layer is already attached and skips the build every time.
  EXISTING_LAYER="$(aws lambda get-function-configuration --function-name "$SCAN_HTML_FN" --output json \
    | jq -r --arg name "$CHROMIUM_LAYER_NAME" '(.Layers // [])[] | select(.Arn | contains($name)) | .Arn' | head -1)"

  if [ -n "$EXISTING_LAYER" ]; then
    log "Chromium layer already attached to $SCAN_HTML_FN — skipping rebuild"
  else
    log "Building & publishing Chromium layer for $SCAN_HTML_FN"
    CHROMIUM_RANGE="$(jq -r '.dependencies["@sparticuz/chromium-min"]' services/aws-lambda-scan-html/package.json)"
    LAYER_TMPDIR="$(mktemp -d)"
    mkdir -p "$LAYER_TMPDIR/nodejs"
    npm install "@sparticuz/chromium@${CHROMIUM_RANGE}" --prefix "$LAYER_TMPDIR/nodejs" --no-save --omit=dev >/dev/null
    (cd "$LAYER_TMPDIR" && zip -rq chromium-layer.zip nodejs)

    # PublishLayerVersion's direct --zip-file upload caps at ~70MB (the
    # request body is base64-inline); the real Chromium binary pushes past
    # that, so stage it in S3 and reference it by location instead.
    LAYER_S3_KEY="chromium-layer/${CHROMIUM_LAYER_NAME}-$(date +%s).zip"
    aws s3 cp "$LAYER_TMPDIR/chromium-layer.zip" "s3://${DEPLOY_ARTIFACTS_BUCKET}/${LAYER_S3_KEY}" >/dev/null

    LAYER_ARN="$(aws lambda publish-layer-version \
      --layer-name "$CHROMIUM_LAYER_NAME" \
      --description "@sparticuz/chromium ${CHROMIUM_RANGE}" \
      --content "S3Bucket=${DEPLOY_ARTIFACTS_BUCKET},S3Key=${LAYER_S3_KEY}" \
      --compatible-runtimes nodejs22.x \
      --compatible-architectures x86_64 \
      --query LayerVersionArn --output text)"

    aws s3 rm "s3://${DEPLOY_ARTIFACTS_BUCKET}/${LAYER_S3_KEY}" >/dev/null 2>&1 || true
    rm -rf "$LAYER_TMPDIR"

    aws lambda update-function-configuration --function-name "$SCAN_HTML_FN" --layers "$LAYER_ARN" >/dev/null
    aws lambda wait function-updated --function-name "$SCAN_HTML_FN"
  fi

  log "Building & deploying aws-lambda-verapdf-interface -> $(fn_name verapdf_interface)"
  (
    cd services/aws-lambda-verapdf-interface
    mvn -q clean package
    JAR="$(find target -maxdepth 1 -name '*.jar' ! -name 'original-*' | head -1)"
    aws lambda update-function-code --function-name "$(fn_name verapdf_interface)" --zip-file "fileb://$JAR" >/dev/null
  )

  log "Building & deploying apps/backend -> $(fn_name backend)"
  (
    cd apps/backend
    npm install
    npx esbuild index.ts --bundle --platform=node --outdir=dist --external:@aws-sdk --loader:.node=file
    cd dist
    rm -f lambda.zip
    zip -rq lambda.zip index.js
    aws lambda update-function-code --function-name "$(fn_name backend)" --zip-file "fileb://lambda.zip" >/dev/null
    rm -f lambda.zip
  )
else
  log "Skipping Lambda deploys (--skip-lambdas)"
fi

# ---------------------------------------------------------------------------
if [ "$SKIP_FRONTEND" = 0 ]; then
  log "Building & publishing frontend -> s3://$FRONTEND_BUCKET"
  (
    cd apps/frontend
    # .local, not .env.production directly: Vite loads .env.production.local
    # with higher precedence, and it's already covered by the repo's
    # .gitignore (`.env.*`) — this deployment's own values never touch the
    # tracked .env.production, which holds the real org's production config
    # for the existing (non-Terraform) GitHub Actions deploy in
    # .github/workflows/deploy-apps.yml.
    echo "$TF_OUT" | jq -r '.frontend_env_hints.value | to_entries[] | "\(.key)=\"\(.value)\""' > .env.production.local
    npm install
    npx vite build --mode production
    aws s3 sync --delete ./dist "s3://$FRONTEND_BUCKET"
    aws cloudfront create-invalidation --distribution-id "$FRONTEND_DIST_ID" --paths "/*" >/dev/null
  )
else
  log "Skipping frontend deploy (--skip-frontend)"
fi

# ---------------------------------------------------------------------------
if [ -n "$CREATE_USER_EMAIL" ]; then
  log "Creating Cognito user: $CREATE_USER_EMAIL"

  # Set a permanent password directly rather than emailing a temporary one:
  # Cognito's own account-verification email (separate from the app's own
  # SES setup) works without any SES configuration, but a fresh install
  # shouldn't have its first login depend on any email deliverability at all.
  aws cognito-idp admin-create-user \
    --user-pool-id "$COGNITO_USER_POOL_ID" \
    --username "$CREATE_USER_EMAIL" \
    --user-attributes Name=email,Value="$CREATE_USER_EMAIL" Name=email_verified,Value=true \
    --message-action SUPPRESS >/dev/null

  # Guaranteed to satisfy the pool's password policy (min 12 chars, upper +
  # lower + number; symbols not required) regardless of what the random
  # portion happens to contain.
  CREATE_USER_PASSWORD="$(openssl rand -hex 9)Aa1!"
  aws cognito-idp admin-set-user-password \
    --user-pool-id "$COGNITO_USER_POOL_ID" \
    --username "$CREATE_USER_EMAIL" \
    --password "$CREATE_USER_PASSWORD" \
    --permanent >/dev/null

  echo ""
  echo "  Email:    $CREATE_USER_EMAIL"
  echo "  Password: $CREATE_USER_PASSWORD"
  echo ""
  echo "Save this now — it is not stored anywhere and won't be shown again."
  echo "(This becomes the first admin account automatically, via the"
  echo " Cognito PostConfirmation trigger's first-user bootstrap.)"
fi

log "Done. Frontend: $FRONTEND_URL"
