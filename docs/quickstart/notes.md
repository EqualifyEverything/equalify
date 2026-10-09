# Quickstart Notes

Additional notes on configuring and maintaining an Equalify instance
provisioned with the [Quickstart](../../QUICKSTART.md).

- **First admin bootstrap**: the very first user to complete sign-in
  automatically becomes an admin (`users.type = 'admin'`) — a Cognito
  PostConfirmation trigger checks whether the `users` table is empty and, if
  so, inserts the new user as admin instead of the default `member`. This
  only fires once, on that first confirmation; every subsequent signup
  lands as a regular member. If you ever need to promote someone else
  later, that's a direct `UPDATE users SET type='admin' WHERE email='...'`
  (through the bastion tunnel — see [Step 6](../../QUICKSTART.md#6-deploy-the-application)) — there's no UI for it yet.
- **Custom domain**: if you set `domain_name` + `route53_zone_id` in
  `terraform.tfvars`, Terraform provisions ACM certs and Route53 records for
  `app./api./graphql.<domain>` automatically — `terraform output` will
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
