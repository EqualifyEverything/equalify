# Quickstart Troubleshooting

Fixes for common problems when following the [Quickstart](../../QUICKSTART.md).

## `terraform apply` fails with "No valid credential sources found"

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
