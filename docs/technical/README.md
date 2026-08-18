# Equalify Technical Documentation

This folder contains technical documentation for developers working on or deploying Equalify.

## Documentation Index

| Document | Description |
|----------|-------------|
| [Architecture Overview](architecture-overview.md) | High-level system architecture and component overview |
| [Backend API](backend-api.md) | API endpoints, authentication, and database access |
| [Scanning Services](scanning-services.md) | HTML and PDF scanning microservices |
| [Frontend Guide](frontend-guide.md) | React application structure and development |
| [Database Schema](database-schema.md) | PostgreSQL tables and relationships |
| [Deployment Guide](deployment-guide.md) | AWS deployment and configuration |
| [Contributing](contributing.md) | Development workflow and contribution guidelines |

## Quick Start

### Prerequisites
- Node.js 22.x
- npm
- AWS CLI v2 (for deployment)

### Development Setup
```bash
# Clone the repository
git clone https://github.com/equalifyEverything/equalify.git
cd equalify

# Install dependencies
npm install

# Start frontend development
cd apps/frontend
npm run start:staging
```

### Deploy to Staging
```bash
# Frontend
cd apps/frontend && npm run build:staging

# Backend
cd apps/backend && npm run build:staging
```

To provision a brand-new instance from scratch (infrastructure + app code), see the root [`QUICKSTART.md`](../../QUICKSTART.md).

## Technology Stack

| Layer | Technologies |
|-------|--------------|
| Frontend | React 19, TypeScript, Vite, TanStack Query |
| Backend | Node.js, AWS Lambda, PostgreSQL, Hasura |
| Scanning | Chromium, axe-core, veraPDF |
| Infrastructure | AWS (Lambda, SQS, S3, CloudFront, Cognito), provisioned via Terraform |

## Architecture Overview

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Frontend  │────▶│   Backend   │────▶│  Database   │
│   (React)   │     │   (Lambda)  │     │ (PostgreSQL)│
└─────────────┘     └─────────────┘     └─────────────┘
                          │
                          ▼
                    ┌─────────────┐
                    │ SQS Router  │
                    └─────────────┘
                          │
              ┌───────────┴───────────┐
              ▼                       ▼
        ┌───────────┐           ┌───────────┐
        │   HTML    │           │    PDF    │
        │  Scanner  │           │  Scanner  │
        └───────────┘           └───────────┘
```

## Support

- **GitHub Issues**: Technical bugs and feature requests
- **Documentation**: This folder and inline code comments
- **Newsletter**: [it.uic.edu/accessibility/engineering](https://it.uic.edu/accessibility/engineering)

---
*Equalify is an open-source project by UIC Technology Solutions.*
