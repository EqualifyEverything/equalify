# Backend API Reference

The Equalify backend is a single AWS Lambda function that acts as a router, directing requests to appropriate handlers based on the URL path and authentication status.

## Entry Point

The main handler (`apps/backend/index.ts`) routes requests based on path prefixes:

| Path Prefix | Router | Authentication |
|-------------|--------|----------------|
| `/public` | `publicRouter` | None |
| `/auth` | `authRouter` | JWT required |
| `/internal` | `internalRouter` | Internal only |
| `/scheduled` | `scheduledRouter` | EventBridge |
| `/hasura` | `hasuraRouter` | Webhook secret |
| Cognito triggers | `cognitoRouter` | Cognito events |

## Route Organization

### Public Routes (`/public`)
Endpoints that don't require authentication:

| Endpoint | Description |
|----------|-------------|
| `createUser` | Sign up a new user |
| `authenticate` | Log in and issue tokens |
| `checkIfUserExists` | Check if an email is already registered |
| `scanWebhook` | Receives scan results from scanner Lambdas |
| `getAuditChart` | Get chart data for a shared/public audit |
| `getAuditTable` | Get paginated blocker table for a shared/public audit |
| `exportAuditTable` | Export blocker table for a shared/public audit |
| `getAuditSummary` | Get summary stats for a shared/public audit |
| `getAuditSummaryFast` | Get cached/fast summary stats for a shared/public audit |
| `putMetrics` | Record frontend telemetry metrics |
| `getBlockerSummary` | Get AI-generated blocker summary |
| `flagBlockerSummary` | Flag an AI-generated blocker summary |
| `requestAccess` | Request access under SSO (admin review required) |

Note: the router matches on path only (`apps/backend/utils/router.ts`) — it does not check HTTP method, so the Method column below reflects convention, not enforcement.

### Auth Routes (`/auth`)
Endpoints requiring a valid JWT token:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `getAccount` | GET | Retrieve user account details |
| `updateUser` | POST | Update user profile |
| `saveAudit` | POST | Create a new audit |
| `updateAudit` | POST | Modify an existing audit |
| `deleteAudit` | POST | Delete an audit |
| `rescanAudit` | POST | Trigger a new scan for an audit |
| `getAuditDetails` | GET | Get audit configuration |
| `getAuditResults` | GET | Get scan results with blockers |
| `getAuditChart` | GET | Get chart data for blockers over time |
| `getAuditTable` | GET | Get paginated blocker table data |
| `getAuditProgress` | GET | Get current scan progress |
| `getAuditSummary` | GET | Get summary stats for an audit |
| `getAuditSummaryFast` | GET | Get cached/fast summary stats for an audit |
| `exportAuditTable` | GET | Export blocker table as CSV |
| `exportAuditTablePdfSourceLinks` | GET | Export PDF source links for blockers |
| `getLogs` | GET | Get activity logs |
| `inviteUser` | POST | Invite a new user |
| `trackUser` | POST | Track user analytics events |
| `trackSession` | POST | Record an authenticated app load or login (optionally with SSO org fields from Microsoft Graph) for monthly KPIs |
| `saveQuickScan` | POST | Run and save a one-off quick scan |
| `getQuickScans` | GET | List quick scans |
| `fetchRemoteCsv` | POST | Fetch a remote CSV of URLs |
| `syncFromRemoteCsv` | POST | Sync audit URLs from a remote CSV |
| `crawlUrl` | POST | Crawl a URL to discover pages |
| `getBedrockModels` | GET | List available Bedrock models for AI features |
| `getSystemStats` | GET | Get system-wide usage stats plus a month-over-month KPI series (admin) |
| `getAccessRequests` | GET | List pending SSO access requests (admin) |
| `reviewAccessRequest` | POST | Approve or deny an SSO access request (admin) |

### Scheduled Routes (`/scheduled`)
Endpoints triggered by AWS EventBridge:

- **`runEveryMinute`** - Processes scheduled audits and triggers scans
- **`runEveryDay`** - Daily scheduled tasks (e.g. processing scheduled audit emails)

### Hasura Routes (`/hasura`)
Webhook endpoints for Hasura event triggers (protected by webhook secret).

## Authentication Flow

### JWT Verification

```typescript
// For Cognito tokens
const verifier = CognitoJwtVerifier.create({
  userPoolId: process.env.USER_POOL_ID,
  tokenUse: "id",
  clientId: process.env.WEB_CLIENT_ID
});
const claims = await verifier.verify(token);

// For SSO tokens
const rawClaims = await verifySsoToken(token);
const { normalizedClaims, hasuraClaims } = await ensureSsoUser(rawClaims);
```

### Token Structure

Tokens include Hasura claims for GraphQL authorization:
```json
{
  "sub": "user-uuid",
  "email": "user@example.com",
  "https://hasura.io/jwt/claims": {
    "x-hasura-allowed-roles": ["user"],
    "x-hasura-default-role": "user",
    "x-hasura-user-id": "user-uuid",
    "x-hasura-org-id": "org-uuid"
  }
}
```

Note: Hasura only defines `anonymous` and `user` roles — there is no `admin` Hasura role. Admin-only endpoints (e.g. `getSystemStats`, `reviewAccessRequest`) are enforced at the Lambda layer, not via Hasura permissions.

## Database Access

### Direct PostgreSQL
Using `serverless-postgres` for connection pooling:

```typescript
import { db } from '#src/utils';

await db.connect();
const result = await db.query({
  text: `SELECT * FROM "audits" WHERE "id" = $1`,
  values: [auditId],
});
await db.clean();
```

### GraphQL via Hasura
For complex queries with relationships:

```typescript
import { graphqlQuery } from '#src/utils';

const response = await graphqlQuery({
  query: `query ($audit_id: uuid!) {
    audits_by_pk(id: $audit_id) {
      scans(order_by: {created_at: desc}, limit: 1) {
        blockers { ... }
      }
    }
  }`,
  variables: { audit_id: auditId },
});
```

## Creating an Audit

When a user creates an audit (`saveAudit`):

1. Insert audit record with configuration
2. Insert all URLs associated with the audit
3. If `saveAndRun` is true:
   - Create a new scan record
   - Invoke the scan router Lambda asynchronously

```typescript
await lambda.send(new InvokeCommand({
  FunctionName: "aws-lambda-scan-sqs-router",
  InvocationType: "Event",
  Payload: JSON.stringify({
    urls: urls.map(url => ({
      auditId, scanId, urlId: url.id,
      url: url.url, type: url.type, isStaging
    }))
  })
}));
```

## Webhook Processing

The `scanWebhook` endpoint processes results from scanners:

1. Validate required fields (`auditId`, `urlId`)
2. Handle failed scans (log error, update progress)
3. For successful scans:
   - Store blockers with content hashing for deduplication
   - Link blockers to messages (accessibility rules) and tags
   - Update scan progress atomically
   - Mark scan complete when all URLs processed, denormalizing `blocker_count`/`equalified_count` onto the `scans` row

## Environment Variables

| Variable | Description |
|----------|-------------|
| `DB_USER` | PostgreSQL username |
| `DB_HOST` | PostgreSQL host |
| `DB_NAME` | Database name |
| `DB_PASSWORD` | Database password / Hasura admin secret |
| `USER_POOL_ID` | Cognito User Pool ID |
| `WEB_CLIENT_ID` | Cognito Web Client ID |
| `SSO_ENABLED` | Enable SSO authentication |
| `SSO_CLIENT_ID` | Azure AD SSO client ID |
| `SSO_TENANT` | Azure AD tenant ID |
| `SSO_JWKS` | Azure AD JWKS endpoint for SSO token verification |
| `WEBHOOKSECRET` | Secret for Hasura webhooks |
| `GRAPHQL_URL` | Hasura GraphQL endpoint used by `graphqlQuery` |
| `SQS_ROUTER_FUNCTION_NAME` | Function name of the SQS Router Lambda invoked from `saveAudit`/`rescanAudit` |
| `CRAWLER_FUNCTION_NAME` | Function name of the crawler Lambda invoked from `crawlUrl` |

---
*For frontend integration details, see the Frontend Guide.*
