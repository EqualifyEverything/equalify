<img src="logo.svg" alt="Equalify Logo" width="300">

**Equalify** is an open-source accessibility scanning and monitoring platform. Point it at your web pages and PDFs, and it finds accessibility errors, tracks them over time, and helps your team resolve them.

Built by the [University of Illinois Chicago (UIC) Technology Solutions](https://it.uic.edu/about/technology-solutions/) Digital Accessibility Engineering team, and open source under the AGPL-3.0.

## Features

- **HTML & PDF scanning** — headless Chromium + [axe-core](https://github.com/dequelabs/axe-core) for pages, [veraPDF](https://verapdf.org/) PDF/UA validation for documents
- **Built for scale** — audits fan out across concurrent AWS Lambda workers, so hundreds of URLs can be scanned in seconds
- **Accessible** — built from the ground up to be usable via screen-readers and other assistive technologies
- **Audits** — schedule recurring scans across many URLs
- **Blocker tracking** — every issue found is tagged, categorized, and trackable to resolution
- **Dashboards & trends** — audit summaries and blocker-count charts over time
- **WordPress integration** — connect your WordPress site to Equalify with the official [plugin](https://github.com/EqualifyEverything/equalify-wp-integration) to keep your audits in sync with your latest pages, posts, and library PDF files
- **AI-assisted summaries** — fully optional LLM-generated summaries of each issue, via AWS Bedrock
- **Quick Scans** — scan a single URL on demand, no setup required
- **Team accounts** — admin/member roles, invites, self-service access requests, and optional SSO (Azure AD)
- **White-label branding** — swap in your own logo
- **CSV export** — take audit results with you
- **Email notifications** — scheduled audit summaries and account emails via SES
- **Always Open Source** — licensed under the AGPL-3.0


## Architecture

Equalify runs entirely on your own AWS account:

- **Frontend** — React + Vite single-page app, served from S3 via CloudFront
- **Backend API** — a Node.js Lambda behind API Gateway, handling auth and business logic
- **Data layer** — PostgreSQL (RDS) with a [Hasura](https://hasura.io/) GraphQL layer (ECS Fargate) for the frontend
- **Scan pipeline** — a router Lambda fans scan requests out over SQS to dedicated HTML and PDF scan Lambdas, which post results back via webhook — see [`services/README.md`](services/README.md) for the full diagram
- **Auth** — AWS Cognito, with optional Azure AD SSO
- **Infrastructure as code** — the entire stack (VPC, RDS, ECS, Lambda, S3/CloudFront, Cognito, SQS) is defined in Terraform under [`infrastructure/`](infrastructure/)

## Installing Equalify

Equalify deploys to your own AWS account with Terraform and a couple of scripts — no manual clicking through the console required.

**[Read the Quickstart Guide →](QUICKSTART.md)**

## Using Equalify

Once installed, sign in and start scanning. For a walkthrough of the dashboard, audits, and blocker management, see the **[user documentation](https://equalify.uic.edu/dashboard)**.

## How to Contribute

Found a bug? [File an issue on GitHub](https://github.com/equalifyEverything/equalify/issues/new) — that's the fastest way to get it in front of us.

Contributing code? PRs are welcome. Read our [contributor guide]('CONTRIBUTE.md') and see [our versioning documentation](VERSIONING.md) for how we version releases and structure commits.

## Get in Touch

- Subscribe to our newsletter: [it.uic.edu/accessibility/engineering](https://it.uic.edu/accessibility/engineering)
- Star or contribute on GitHub: [github.com/equalifyEverything/equalify](https://github.com/equalifyEverything/equalify)

We welcome your questions, feedback, and continued participation.

**Together, we can equalify the internet.**

— Digital Accessibility Engineering, UIC Technology Solutions
