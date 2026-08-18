# Contributing to Equalify

Equalify is an open-source project under the AGPL license. We welcome contributions from the community!

## Getting Started

### Prerequisites

- Node.js 22.x
- npm
- AWS CLI v2 (for deployment)
- Git

### Clone the Repository

```bash
git clone https://github.com/equalifyEverything/equalify.git
cd equalify
```

### Install Dependencies

The project uses npm workspaces:

```bash
npm install
```

This installs dependencies for all workspaces:
- `apps/frontend`
- `apps/backend`
- `services/aws-lambda-scan-html`
- `services/aws-lambda-scan-pdf`
- `services/aws-lambda-verapdf-interface`
- `services/aws-lambda-scan-sqs-router`
- `shared/types`

`services/aws-lambda-crawler` has its own separate `package.json`/lockfile and is not currently part of the root npm workspace.

## Development Workflow

### Frontend Development

```bash
cd apps/frontend
npm run start:staging
```

This starts Vite dev server at `http://localhost:5173` connected to staging APIs.

### Backend Development

The backend runs on AWS Lambda, so local development requires:

1. Make code changes
2. Deploy to staging: `npm run build:staging`
3. Test via staging frontend or API calls

### Testing Changes

1. Create a feature branch
2. Make and test changes locally
3. Deploy to staging environment
4. Verify functionality works correctly
5. Submit a pull request

## Project Structure

```
equalify/
├── apps/
│   ├── backend/          # API Lambda
│   │   ├── routes/       # API endpoints
│   │   │   ├── auth/     # Authenticated endpoints
│   │   │   ├── public/   # Public endpoints
│   │   │   ├── internal/ # Internal endpoints
│   │   │   └── scheduled/# Scheduled tasks
│   │   └── utils/        # Shared utilities
│   └── frontend/         # React application
│       └── src/
│           ├── components/
│           ├── routes/
│           ├── queries/
│           └── hooks/
├── services/             # Scanning microservices
│   ├── aws-lambda-crawler/
│   ├── aws-lambda-scan-sqs-router/
│   ├── aws-lambda-scan-html/
│   ├── aws-lambda-scan-pdf/
│   └── aws-lambda-verapdf-interface/
├── shared/               # Shared code
│   ├── types/            # TypeScript types
│   └── convertors/       # Result converters
├── db/                   # PostgreSQL schema, migrations, Hasura metadata
├── infrastructure/       # Terraform-managed AWS infrastructure
└── scripts/              # Deployment scripts
```

## Code Style

### TypeScript

- Use TypeScript for all new code
- Enable strict mode
- Use explicit types for function parameters and return values

### Naming Conventions

- **Files**: camelCase for utilities, PascalCase for components
- **Functions**: camelCase
- **Components**: PascalCase
- **Constants**: UPPER_SNAKE_CASE

### Frontend Guidelines

- Use functional components with hooks
- Use SCSS Modules for styling
- Use Radix UI primitives for accessible components
- Follow React 19 best practices

### Backend Guidelines

- Keep route handlers focused and small
- Use utility functions for shared logic
- Always clean up database connections
- Handle errors gracefully with appropriate status codes

## Adding New Features

### Adding an API Endpoint

1. Create handler in appropriate route folder:
   ```typescript
   // apps/backend/routes/auth/myFeature.ts
   import { db, event } from '#src/utils';
   
   export const myFeature = async () => {
     // Implementation
   };
   ```

2. Export from route index:
   ```typescript
   // apps/backend/routes/auth/index.ts
   export { myFeature } from './myFeature';
   ```

3. The router automatically maps the export name to the endpoint.

### Adding a Frontend Page

1. Create page component:
   ```typescript
   // apps/frontend/src/routes/MyPage.tsx
   export const MyPage = () => {
     return <div>My Page</div>;
   };
   ```

2. Export from routes index:
   ```typescript
   // apps/frontend/src/routes/index.ts
   export { MyPage } from './MyPage';
   ```

3. Add route in App.tsx router configuration.

### Adding a New Scanner

1. Create service directory in `services/`
2. Implement SQS message handler
3. Create result converter in `shared/convertors/`
4. Add queue routing in `aws-lambda-scan-sqs-router`
5. Update type definitions in `shared/types/`

## Accessibility Requirements

As an accessibility tool, Equalify must meet high accessibility standards:

- All features must be keyboard navigable
- Use semantic HTML elements
- Include proper ARIA attributes where needed
- Ensure sufficient color contrast
- Test with screen readers
- Follow WCAG 2.1 AA guidelines

## Pull Request Process

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/my-feature`
3. **Commit** your changes with clear messages
4. **Push** to your fork
5. **Open** a pull request against `main`

### PR Requirements

- Clear description of changes
- Tests pass (if applicable)
- No accessibility regressions
- Code follows project style
- Documentation updated if needed

## Reporting Issues

Use GitHub Issues for:
- Bug reports
- Feature requests
- Documentation improvements

Include:
- Clear description
- Steps to reproduce (for bugs)
- Expected vs actual behavior
- Environment details

## Getting Help

- GitHub Issues for technical questions
- Subscribe to newsletter: [it.uic.edu/accessibility/engineering](https://it.uic.edu/accessibility/engineering)

## License

Equalify is licensed under AGPL-3.0. Contributions are subject to the same license.

---
*For architecture details, see the Architecture Overview.*
