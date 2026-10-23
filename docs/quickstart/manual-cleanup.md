# Manual Cleanup Steps

`scripts/destroy-app.sh` (see [Cleanup](../../QUICKSTART.md#cleanup-avoid-ongoing-aws-charges)
in the Quickstart) automates everything below. This is what it's doing
under the hood, if you'd rather run it by hand or adapt it.

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
   bucket + DynamoDB lock table from [step 2 of the install](../../QUICKSTART.md#2-bootstrap-terraform-remote-state)). Its ongoing
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
