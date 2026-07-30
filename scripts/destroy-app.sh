#!/usr/bin/env bash
#
# Tears down the AWS infrastructure Terraform provisioned (infrastructure/),
# to stop it accruing charges. See "Cleanup" in QUICKSTART.md.
#
# THIS PERMANENTLY DELETES ALL SCAN DATA, AUDITS, AND USERS. Only run this
# against a disposable test/eval stack.
#
# Usage: scripts/destroy-app.sh [--yes] [--skip-final-snapshot] [--destroy-bootstrap]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
INFRA_DIR="$REPO_ROOT/infrastructure"

AUTO_YES=0
SKIP_FINAL_SNAPSHOT=0
DESTROY_BOOTSTRAP=0

usage() {
  cat <<EOF
Usage: $0 [options]

Destroys the main Terraform stack (RDS, Hasura/ECS, Lambdas, VPC/NAT,
S3+CloudFront, Cognito, Secrets Manager). Permanently deletes all data.

Options:
  --yes                Skip the interactive confirmation prompt and pass
                        -auto-approve to terraform destroy (for CI/scripted use)
  --skip-final-snapshot Skip RDS's final snapshot (terraform -var db_skip_final_snapshot=true)
  --destroy-bootstrap  Also tear down the bootstrap remote-state stack
                        (S3 state bucket + DynamoDB lock table) afterward.
                        Its ongoing cost is a few cents/month; most people
                        leave it. Once destroyed, this Terraform config can
                        no longer manage the stack it just tore down.
  -h, --help           Show this help
EOF
}

while [ $# -gt 0 ]; do
  case "$1" in
    --yes) AUTO_YES=1; shift ;;
    --skip-final-snapshot) SKIP_FINAL_SNAPSHOT=1; shift ;;
    --destroy-bootstrap) DESTROY_BOOTSTRAP=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage; exit 1 ;;
  esac
done

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[1;33m%s\033[0m\n' "$1"; }
die() { echo "Error: $1" >&2; exit 1; }

require() {
  command -v "$1" >/dev/null 2>&1 || die "'$1' is required but not found in PATH. $2"
}

require terraform "Install: https://developer.hashicorp.com/terraform/install"
require aws "Install: https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html"
require jq "Install: apt install jq / brew install jq"

log "Reading Terraform outputs"
cd "$INFRA_DIR"
TF_OUT="$(terraform output -json)"

[ "$(echo "$TF_OUT" | jq -r '.lambda_function_names // empty')" != "" ] \
  || die "No Terraform outputs found — nothing to destroy, or state isn't reachable."

out() { echo "$TF_OUT" | jq -r "$1"; }

FRONTEND_BUCKET="$(out '.frontend_bucket_name.value')"
FRONTEND_URL="$(out '.frontend_url.value')"
RDS_INSTANCE_ID="$(out '.rds_instance_id.value')"
DB_PASSWORD_SECRET_ARN="$(out '.db_password_secret_arn.value')"
HASURA_ADMIN_SECRET_ARN="$(out '.hasura_admin_secret_arn.value')"
WEBHOOK_SECRET_ARN="$(out '.webhook_secret_arn.value')"
SSO_CONFIG_SECRET_ARN="$(out '.sso_config_secret_arn.value')"

warn "This will PERMANENTLY DESTROY the Equalify stack at:"
warn "  $FRONTEND_URL"
warn "All scan data, audits, and users will be unrecoverable."
if [ "$AUTO_YES" = 0 ]; then
  read -r -p 'Type "destroy" to continue: ' CONFIRM
  [ "$CONFIRM" = "destroy" ] || die "Confirmation did not match. Aborted, nothing was destroyed."
fi

log "Emptying frontend S3 bucket: $FRONTEND_BUCKET"
if [ -n "$FRONTEND_BUCKET" ] && [ "$FRONTEND_BUCKET" != "null" ]; then
  aws s3 rm "s3://$FRONTEND_BUCKET" --recursive || true
fi

log "Disabling RDS deletion protection: $RDS_INSTANCE_ID"
aws rds modify-db-instance --db-instance-identifier "$RDS_INSTANCE_ID" \
  --no-deletion-protection --apply-immediately >/dev/null

echo "Waiting for $RDS_INSTANCE_ID to leave 'modifying' state..."
for _ in $(seq 1 60); do
  STATUS="$(aws rds describe-db-instances --db-instance-identifier "$RDS_INSTANCE_ID" \
    --query 'DBInstances[0].DBInstanceStatus' --output text)"
  [ "$STATUS" != "modifying" ] && break
  sleep 10
done

log "Running terraform destroy"
DESTROY_ARGS=()
[ "$SKIP_FINAL_SNAPSHOT" = 1 ] && DESTROY_ARGS+=(-var db_skip_final_snapshot=true)
[ "$AUTO_YES" = 1 ] && DESTROY_ARGS+=(-auto-approve)
if [ "${#DESTROY_ARGS[@]}" -eq 0 ]; then
  terraform destroy
else
  terraform destroy "${DESTROY_ARGS[@]}"
fi

log "Force-deleting Secrets Manager entries (skipping their 7-day recovery window)"
for arn in "$DB_PASSWORD_SECRET_ARN" "$HASURA_ADMIN_SECRET_ARN" "$WEBHOOK_SECRET_ARN" "$SSO_CONFIG_SECRET_ARN"; do
  [ -n "$arn" ] && [ "$arn" != "null" ] || continue
  aws secretsmanager delete-secret --secret-id "$arn" --force-delete-without-recovery >/dev/null 2>&1 || true
done

if [ "$DESTROY_BOOTSTRAP" = 1 ]; then
  log "Tearing down the bootstrap remote-state stack"
  cd "$INFRA_DIR/bootstrap"
  BOOT_OUT="$(terraform output -json)"
  STATE_BUCKET="$(echo "$BOOT_OUT" | jq -r '.state_bucket_name.value')"

  if [ "$AUTO_YES" = 0 ]; then
    warn "This also deletes the Terraform state bucket ($STATE_BUCKET) and lock table."
    warn "This config will no longer be able to manage the stack just destroyed."
    read -r -p 'Type "destroy bootstrap" to continue: ' CONFIRM2
    [ "$CONFIRM2" = "destroy bootstrap" ] || die "Confirmation did not match. Bootstrap stack left in place."
  fi

  # aws_s3_bucket.terraform_state has prevent_destroy=true, so it can't be
  # destroyed via terraform. Untrack it (this does NOT touch the real
  # bucket) so `terraform destroy` can remove everything else, then delete
  # the bucket by hand below.
  terraform state rm \
    aws_s3_bucket.terraform_state \
    aws_s3_bucket_versioning.terraform_state \
    aws_s3_bucket_server_side_encryption_configuration.terraform_state \
    aws_s3_bucket_public_access_block.terraform_state \
    >/dev/null

  if [ "$AUTO_YES" = 1 ]; then
    terraform destroy -auto-approve
  else
    terraform destroy
  fi

  echo "Emptying and deleting state bucket: $STATE_BUCKET"
  aws s3api list-object-versions --bucket "$STATE_BUCKET" --output json \
    | jq -r '(.Versions // [])[], (.DeleteMarkers // [])[] | "\(.Key)\t\(.VersionId)"' \
    | while IFS=$'\t' read -r key vid; do
        aws s3api delete-object --bucket "$STATE_BUCKET" --key "$key" --version-id "$vid" >/dev/null
      done
  aws s3api delete-bucket --bucket "$STATE_BUCKET"
fi

log "Done."
