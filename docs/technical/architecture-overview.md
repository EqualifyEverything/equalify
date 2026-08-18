# Architecture Overview

Equalify is a web accessibility scanning and monitoring platform built on a serverless AWS architecture. This document provides a high-level overview of the system's components and how they interact.

## System Architecture

Equalify follows a modern serverless architecture with clear separation between the frontend, backend API, and scanning microservices.

### Core Components

| Component | Technology | Purpose |
|-----------|------------|---------|
| Frontend | React + Vite | User interface for managing audits and viewing results |
| Backend API | Node.js (AWS Lambda) | Authentication, audit management, and data access |
| Crawler | TypeScript Lambda | Optional sitemap-based URL discovery helper |
| Scan Router | TypeScript Lambda | Routes scan requests to appropriate scanners |
| HTML Scanner | TypeScript Lambda + Chromium | Scans web pages using axe-core |
| PDF Scanner | TypeScript + Java Lambdas | Scans PDF documents using veraPDF |
| Database | PostgreSQL (via Hasura) | Stores audits, URLs, blockers, and scan results |
| Message Queue | AWS SQS (FIFO) | Manages scan job distribution |
| Infrastructure | Terraform | Provisions the full AWS stack (VPC, RDS, Lambda, S3/CloudFront, Cognito, SQS) |

### Data Flow

1. **User creates an audit** → Frontend sends request to Backend API
2. **Audit is saved** → Backend stores audit and URLs in PostgreSQL
3. **Scan is triggered** → Backend invokes the SQS Router Lambda
4. **Jobs are queued** → Router distributes URLs to HTML or PDF SQS queues
5. **Scanners process jobs** → Lambdas consume from SQS and perform scans
6. **Results are returned** → Scanners POST results to the webhook endpoint
7. **Data is stored** → Backend processes and stores blockers in the database
8. **User views results** → Frontend queries and displays scan results

## Repository Structure

```
equalify/
├── apps/
│   ├── backend/          # API Lambda (Express-like router)
│   │   ├── routes/       # API endpoints organized by auth level
│   │   └── utils/        # Shared utilities (DB, auth, etc.)
│   └── frontend/         # React SPA
│       ├── src/
│       │   ├── components/   # Reusable UI components
│       │   ├── routes/       # Page components
│       │   ├── queries/      # API/GraphQL queries
│       │   └── hooks/        # Custom React hooks
│       └── public/
├── services/
│   ├── aws-lambda-crawler/           # Sitemap-based URL discovery
│   ├── aws-lambda-scan-sqs-router/   # Job distribution
│   ├── aws-lambda-scan-html/         # HTML/web scanning
│   ├── aws-lambda-scan-pdf/          # PDF scanning orchestrator
│   └── aws-lambda-verapdf-interface/ # Java PDF scanner
├── shared/
│   ├── types/            # TypeScript types and Zod schemas
│   └── convertors/       # Result format converters
├── db/                   # PostgreSQL schema, migrations, Hasura metadata
├── infrastructure/       # Terraform-managed AWS infrastructure
├── scripts/              # Deployment scripts (deploy-app.sh, destroy-app.sh)
└── aws-layers/           # Lambda layer build configuration
```

## Authentication

Equalify supports two authentication mechanisms:

- **AWS Cognito**: Traditional username/password authentication with JWT tokens
- **SSO (Single Sign-On)**: Enterprise authentication via Azure AD/MSAL

Both methods issue JWT tokens that are validated by the backend API before processing authenticated requests.

## Technology Stack

### Frontend
- **React 19** with TypeScript
- **Vite** for build tooling
- **TanStack Query** for data fetching
- **AWS Amplify** for authentication
- **Radix UI** for accessible components
- **Recharts** for data visualization

### Backend
- **Node.js** running on AWS Lambda
- **PostgreSQL** database with Hasura GraphQL
- **AWS SDK** for Lambda invocation, SES, and S3
- **Zod** for runtime validation

### Infrastructure
- **Terraform** for infrastructure-as-code provisioning
- **AWS Lambda** for serverless compute
- **AWS SQS** (FIFO queues) for job management
- **AWS S3** for static hosting
- **AWS CloudFront** for CDN
- **AWS Cognito** for authentication
- **Hasura on ECS Fargate** (behind an ALB) for the GraphQL layer

---
*For detailed information about specific components, see the other guides in this folder.*
