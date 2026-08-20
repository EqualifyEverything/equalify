# Frontend Guide

The Equalify frontend is a React single-page application built with Vite, providing an accessible interface for managing audits and viewing accessibility scan results.

## Technology Stack

- **React 19** - UI framework
- **TypeScript** - Type safety
- **Vite** - Build tool and dev server
- **TanStack Query** - Server state management
- **React Router v7** - Client-side routing
- **AWS Amplify** - Authentication
- **Radix UI** - Accessible component primitives
- **Recharts** - Data visualization
- **Zustand** - Client state management
- **SCSS Modules** - Scoped styling

## Project Structure

```
apps/frontend/
├── src/
│   ├── main.tsx              # Application entry point
│   ├── App.tsx               # Root component with routing
│   ├── components/           # Reusable UI components
│   ├── routes/               # Page-level components
│   ├── queries/              # API query functions
│   ├── hooks/                # Custom React hooks
│   ├── utils/                # Helper functions
│   ├── telemetry/            # Query/error telemetry reporters (CloudWatch, console)
│   └── global-styles/        # Global SCSS styles
├── public/                   # Static assets
├── index.html                # HTML template
└── vite.config.ts            # Vite configuration
```

## Key Pages

### Audits (`/audits`)
The main dashboard displaying all user audits with status indicators.

### Audit Detail (`/audits/:auditId`)
Displays scan results for a specific audit including:
- Blockers over time chart
- Blocker details table
- Scan progress indicator
- Re-scan functionality

### Build Audit (`/audits/build`)
Form for creating new audits with:
- Audit name and scan frequency
- URL input (manual or CSV upload)
- Email notification settings

### Account (`/account`)
User profile management, plus user invites and system administration for admins.

### Logs (`/logs`)
Activity history for compliance tracking.

## Authentication

### Cognito Authentication
```typescript
import { Amplify } from "aws-amplify";
import * as Auth from "aws-amplify/auth";

Amplify.configure({
  Auth: {
    Cognito: {
      userPoolId: import.meta.env.VITE_USERPOOLID,
      userPoolClientId: import.meta.env.VITE_USERPOOLWEBCLIENTID,
    },
  },
  // ... API configuration
});
```

### SSO Authentication
For enterprise SSO via Azure AD:

```typescript
import { PublicClientApplication } from '@azure/msal-browser';

const msalInstance = new PublicClientApplication({
  auth: {
    clientId: import.meta.env.VITE_SSO_CLIENT_ID,
    authority: import.meta.env.VITE_SSO_AUTHORITY,
    redirectUri: window.location.origin + '/redirect.html',
  },
});
```

### Token Handling
Tokens are automatically included in API requests:

```typescript
// Check for SSO token first
const ssoToken = localStorage.getItem('sso_token');
if (ssoToken) {
  return { Authorization: `Bearer ${ssoToken}` };
}
// Fallback to Cognito session
```

## API Integration

### REST API
Using AWS Amplify REST client, imported as a namespace (`API.get`/`API.post`):

```typescript
import * as API from 'aws-amplify/api';

// GET request
const response = await API.get({
  apiName: 'auth',
  path: '/getAuditResults',
  options: { queryParams: { id: auditId } }
}).response;

// POST request
const response = await API.post({
  apiName: 'auth',
  path: '/saveAudit',
  options: { body: auditData }
}).response;
```

Two REST API names are configured (`App.tsx`): `auth` for authenticated endpoints and `public` for shared/public views (e.g. `apiName: isShared ? 'public' : 'auth'`).

### GraphQL
For real-time updates and complex queries:

```typescript
import * as API from 'aws-amplify/api';

const client = API.generateClient();
const result = await client.graphql({
  query: /* GraphQL query */,
  variables: { audit_id: id }
});
```

### TanStack Query
For caching and state management:

```typescript
import { useQuery, useMutation } from '@tanstack/react-query';

// Query with caching
const { data, isLoading } = useQuery({
  queryKey: ['audit', auditId],
  queryFn: () => fetchAuditDetails(auditId),
});

// Mutation with optimistic updates
const mutation = useMutation({
  mutationFn: saveAudit,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['audits'] });
  },
});
```

## Key Components

### Navigation
Global navigation with authentication-aware links.

### AuditsTable
Sortable, filterable table of all audits with status badges.

### BlockersTable
Paginated table of accessibility blockers with:
- Message content
- HTML snippet preview
- WCAG tag filtering
- URL filtering

### AuditPagesInput
URL entry component supporting:
- Manual URL input
- CSV file upload
- Sitemap detection

### Card
Consistent card layout component for content sections.

### StyledButton
Accessible button component with variants.

## State Management

### Global State (Zustand)
For cross-component state:

```typescript
import { create } from 'zustand';

const useGlobalStore = create((set) => ({
  user: null,
  setUser: (user) => set({ user }),
}));
```

### URL State
Route parameters for shareable views:

```typescript
import { useParams, useSearchParams } from 'react-router-dom';

const { id } = useParams();
const [searchParams, setSearchParams] = useSearchParams();
```

## Styling

### SCSS Modules
Component-scoped styles:

```scss
// Component.module.scss
@use "../global-styles/variables.module.scss" as variables;

.container {
  padding: variables.$spacing;

  .title {
    font-size: 1.25rem;
  }
}
```

```tsx
import styles from './Component.module.scss';

<div className={styles.container}>
  <h1 className={styles.title}>Title</h1>
</div>
```

### SCSS Design Tokens
Global design tokens (spacing, colors, etc.) are defined as SCSS variables in `global-styles/variables.module.scss` and consumed via `@use`:

```scss
// Component.module.scss
@use "../global-styles/variables.module.scss" as variables;

.container {
  padding: variables.$spacing;
}
```

Dark mode is applied via a `body.dark` class selector (`global-styles/dark.scss`), not CSS custom properties or a `data-theme` attribute.

## Environment Variables

| Variable | Description |
|----------|-------------|
| `VITE_USERPOOLID` | Cognito User Pool ID |
| `VITE_USERPOOLWEBCLIENTID` | Cognito Client ID |
| `VITE_API_URL` | Backend API base URL |
| `VITE_GRAPHQL_URL` | Hasura GraphQL endpoint |
| `VITE_SSO_CLIENT_ID` | Azure AD client ID |
| `VITE_SSO_AUTHORITY` | Azure AD authority URL |
| `VITE_SSO_ENABLED` | Feature flag to enable SSO login |
| `VITE_APP_DOMAIN` | Domain used for Cognito cookie-based token storage |
| `VITE_GRAPHQL_WSS` | Hasura GraphQL websocket endpoint (subscriptions) |
| `VITE_BRANCH` | Deployment environment name, used to select telemetry target |
| `VITE_TELEMETRY_ENABLED` | Feature flag to enable telemetry reporting |
| `VITE_TELEMETRY_ENDPOINT` | CloudWatch telemetry reporter endpoint |

## Build & Deployment

### Development
```bash
# Start dev server (staging)
npm run start:staging

# Start dev server (production)
npm run start:prod
```

### Production Build
```bash
# Build and deploy to staging
npm run build:staging

# Build and deploy to production
npm run build:prod
```

Deployment uses:
- AWS S3 for static hosting
- CloudFront for CDN and caching

## Accessibility

As an accessibility tool, the frontend follows strict accessibility standards:

- Radix UI primitives for accessible components
- Semantic HTML structure
- Keyboard navigation support
- Screen reader announcements
- Proper focus management
- Color contrast compliance

---
*For backend API details, see the Backend API Reference.*
