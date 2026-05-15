# Equalify

**Equalify** is an open-source accessibility scanning and monitoring platform. It started as a multi-tenant SaaS product and is now being reshaped into a simpler, self-hostable codebase focused on practical deployment, extensibility, and accessible user experience.

## Table of Contents
- [What Equalify does](#what-equalify-does)
- [Why the project is changing](#why-the-project-is-changing)
- [Monorepo layout](#monorepo-layout)
- [Technology stack](#technology-stack)
- [Getting started](#getting-started)
- [Development workflow](#development-workflow)
- [Accessibility commitment](#accessibility-commitment)
- [Contributing](#contributing)
- [Stay in touch](#stay-in-touch)

## What Equalify does
Equalify helps teams scan, review, and monitor accessibility issues across digital properties.

Current repository signals show a platform-oriented architecture with:
- a frontend app in `apps/frontend`
- a backend app in `apps/backend`
- AWS Lambda services for HTML, PDF, and routing tasks
- shared type packages and supporting infrastructure folders

## Why the project is changing
The maintainers have shared that Equalify is moving toward a more deployable, user-hosted model. The stated priorities are:
- easier deployment
- a more unified codebase
- stronger customization paths
- better documentation
- continued AGPL open-source development
- accessibility as a product requirement, not an afterthought

That direction is especially helpful for new contributors and self-hosters, because it clarifies where the project is headed.

## Monorepo layout
```text
.
├── apps/
│   ├── frontend/
│   └── backend/
├── services/
│   ├── aws-lambda-scan-html/
│   ├── aws-lambda-scan-pdf/
│   ├── aws-lambda-verapdf-interface/
│   └── aws-lambda-scan-sqs-router/
├── shared/
│   └── types/
├── aws-layers/
├── db/
├── test-data/
├── ACCESSIBILITY.md
├── CONTRIBUTE.md
└── package.json
```

## Technology stack
Based on the repository structure, Equalify currently uses:
- **JavaScript/TypeScript workspaces** for the main monorepo
- **AWS-focused services** for scan execution and routing
- a **frontend + backend split** for application delivery
- dedicated documentation for accessibility and contributor setup

## Getting started
### Prerequisites
- Node.js and npm
- AWS CLI v2 if you need the current SSO-based contributor workflow

### Install dependencies
From the repository root:

```bash
npm install
```

### Explore the workspace packages
The root `package.json` declares these workspaces:
- `apps/frontend`
- `apps/backend`
- `services/aws-lambda-scan-html`
- `services/aws-lambda-scan-pdf`
- `services/aws-lambda-verapdf-interface`
- `services/aws-lambda-scan-sqs-router`
- `shared/types`

## Development workflow
The repository already includes deeper setup details in [`CONTRIBUTE.md`](./CONTRIBUTE.md), especially for AWS CLI SSO access.

A practical first-pass workflow for contributors is:
1. clone the repository
2. run `npm install`
3. review `CONTRIBUTE.md`
4. inspect the app and service folders relevant to your change
5. start with small documentation, accessibility, or isolated service improvements

## Accessibility commitment
Equalify's accessibility posture is documented in [`ACCESSIBILITY.md`](./ACCESSIBILITY.md).

Highlights include:
- WCAG 2.2 Level AA as a guiding standard
- testing with automated tools and assistive technologies
- issue-based reporting for accessibility barriers
- accessibility as a core product expectation

## Contributing
Contributions are welcome, especially in areas like:
- deployment simplification
- onboarding documentation
- scanner customization
- accessibility fixes
- clearer local development commands

If you open a pull request, linking the affected workspace or service makes review easier.

## Stay in touch
- UIC Technology Solutions: https://it.uic.edu/about/technology-solutions/
- Accessibility engineering updates: http://it.uic.edu/accessibility/engineering
- Issues and feature discussion: https://github.com/EqualifyEverything/equalify/issues
