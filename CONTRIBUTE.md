### AWS CLI SSO Setup Instructions
Install AWS CLI v2 (required for SSO support) and configure the SSO profile by running:
```
aws configure sso --profile equalifyuic
```

You'll be prompted for:
- SSO session name (optional): equalifyuic-sso
- SSO start URL: https://equalifyuic.awsapps.com/start
- SSO region: us-east-2
- SSO registration scopes: (just press Enter for default)

This will open your browser to authenticate. After logging in:
- Select your AWS account
- Select your role
- CLI default client Region: us-east-2
- CLI default output format: json

In order to easily log in again in the future, simply run this command:
```
aws sso login --profile equalifyuic 
```