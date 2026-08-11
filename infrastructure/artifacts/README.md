# Placeholder Lambda artifacts

`node-stub.zip` and `java-stub.jar` are minimal no-op handlers that Terraform
deploys the first time each Lambda function is created. They exist only so
`terraform apply` has something to upload — the `lambda_function` module sets
`lifecycle { ignore_changes = [filename, source_code_hash] }`, so once the
function exists, Terraform never touches its code again.

Real application code is pushed on top by the existing GitHub Actions
workflows (`.github/workflows/deploy-aws-lambda-*.yml`,
`deploy-apps.yml`), exactly as it does today, via
`aws lambda update-function-code`.

Do not put real application code here — these files are infrastructure
bootstrapping artifacts, not a build output directory.
