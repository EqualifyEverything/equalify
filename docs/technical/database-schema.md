# Database Schema

Equalify uses PostgreSQL as its primary database, accessed via direct queries (serverless-postgres) and GraphQL (Hasura).

## Core Tables

### audits
Stores audit configurations and metadata.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `user_id` | UUID | Owner's user ID (FK) |
| `name` | TEXT | Audit display name |
| `interval` | TEXT | Scan frequency (manual, daily, weekly, etc.) |
| `scheduled_at` | TIMESTAMP | Next scheduled scan time |
| `status` | TEXT | Current status (draft, new, processing, complete, failed) |
| `payload` | JSONB | Full audit configuration |
| `response` | JSONB | Latest scan response |
| `email_notifications` | TEXT | Email alert setting |
| `processed_at` | TIMESTAMP | When the audit was last processed |
| `remote_csv_url` | TEXT | Source URL for remote CSV URL sync |
| `remote_csv_error` | TEXT | Last error from remote CSV sync |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### urls
URLs associated with audits for scanning.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `user_id` | UUID | Owner's user ID (FK) |
| `audit_id` | UUID | Parent audit (FK) |
| `audit_ids` | JSONB | Additional audits this URL belongs to (array, defaults to `[]`) |
| `url` | TEXT | Full URL to scan |
| `type` | TEXT | Content type (html, pdf) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### scans
Individual scan runs for audits.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `audit_id` | UUID | Parent audit (FK) |
| `status` | TEXT | Scan status (processing, complete, failed) |
| `percentage` | NUMERIC | Progress percentage (0-100) |
| `pages` | JSONB | Array of pages to scan |
| `processed_pages` | JSONB | Array of completed page IDs |
| `errors` | JSONB | Array of scan errors |
| `blocker_count` | INTEGER | Denormalized count of blockers found |
| `equalified_count` | INTEGER | Denormalized count of resolved blockers |
| `created_at` | TIMESTAMP | Scan start time |
| `updated_at` | TIMESTAMP | Last update timestamp |

### blockers
Individual accessibility issues found during scans.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `audit_id` | UUID | Parent audit (FK) |
| `scan_id` | UUID | Parent scan (FK) |
| `url_id` | UUID | URL where found (FK) |
| `short_id` | TEXT | Short, per-audit-unique identifier |
| `content` | TEXT | HTML snippet or context |
| `content_normalized` | TEXT | Normalized HTML used for hashing |
| `content_hash_id` | UUID | Hash for deduplication |
| `targets` | JSONB | CSS/DOM target selectors |
| `url_text` | TEXT | Denormalized URL snapshot (fallback if the `urls` row is deleted) |
| `equalified` | BOOLEAN | Marked as resolved |
| `created_at` | TIMESTAMP | Discovery timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### messages
Accessibility rule definitions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `content` | TEXT | Rule/message text |
| `category` | TEXT | Message category |

### blocker_messages
Junction table linking blockers to messages.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `blocker_id` | UUID | Blocker reference (FK) |
| `message_id` | UUID | Message reference (FK) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### tags
WCAG and other accessibility tags.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `content` | TEXT | Tag name (e.g., "wcag2aa") |

### message_tags
Junction table for message-tag relationships.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `message_id` | UUID | Message reference (FK) |
| `tag_id` | UUID | Tag reference (FK) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

### ignored_blockers
Blockers marked as ignored/resolved.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `audit_id` | UUID | Audit reference (FK) |
| `blocker_id` | UUID | Blocker reference (FK) |
| `content_hash_id` | UUID | Hash reference, used to match ignored blockers across scans |
| `created_at` | TIMESTAMP | When ignored |
| `updated_at` | TIMESTAMP | Last update timestamp |

### users
User accounts and profiles.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key (matches Cognito sub) |
| `email` | TEXT | User email address |
| `name` | TEXT | Display name |
| `type` | TEXT | User type (defaults to `member`; also `admin`) |
| `analytics` | JSONB | Analytics/tracking data |
| `apikey` | UUID | API key for programmatic access |
| `created_at` | TIMESTAMP | Account creation |
| `updated_at` | TIMESTAMP | Last update timestamp |

### invites
Pending invitations to join as a user.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `user_id` | UUID | Inviting user (FK) |
| `name` | TEXT | Invitee display name |
| `email` | TEXT | Invitee email address |
| `type` | TEXT | Invited user type |
| `expires_on` | TIMESTAMP | Invitation expiration |

### access_requests
Requests for access under SSO, reviewed by an admin.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `email` | TEXT | Requesting user's email |
| `status` | TEXT | Request status (pending, approved, denied) |
| `created_at` | TIMESTAMP | Request timestamp |

### blocker_llm_summaries
AI-generated summaries and flags for blockers.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `blocker_id` | UUID | Blocker reference (FK) |
| `summary` | TEXT | AI-generated summary text |
| `created_at` | TIMESTAMP | Creation timestamp |

### options
Key/value configuration store.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `key` | TEXT | Option name |
| `value` | JSONB | Option value |

### logs
Activity audit trail.

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `user_id` | UUID | Acting user (FK) |
| `audit_id` | UUID | Related audit (FK, nullable) |
| `message` | TEXT | Log message |
| `data` | JSONB | Structured log details |
| `created_at` | TIMESTAMP | Action timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

User accounts are standalone, and `invites` is how additional users get added.

## Relationships

```
users
  └── audits (many)
        ├── urls (many; also tracked via urls.audit_ids for multi-audit membership)
        ├── scans (many)
        │     └── blockers (many, also FK'd directly to audits and urls)
        └── blockers (many, direct FK)
              ├── blocker_messages (many) ─┬── messages (shared/deduplicated across audits)
              │                            └── message_tags (many) ── tags (shared)
              └── ignored_blockers (via content_hash_id)
```

`messages` and `tags` are deduplicated by content hash and shared across audits/scans — a message or tag is not owned exclusively by one blocker, so the relationship is many-to-many via the `blocker_messages`/`message_tags` junction tables, not a strict one-way hierarchy.

## Content Hashing

Blockers, messages, and tags all use content hashing for deduplication, via a shared `hashStringToUuid` helper (double SHA-256, truncated to a UUID-shaped hex string):

```typescript
const contentNormalized = normalizeHtmlWithVdom(blocker.node);
const contentHashId = hashStringToUuid(contentNormalized);

const messageId = hashStringToUuid(blocker.description + blocker.test);
const tagId = hashStringToUuid(tag);
```

This allows:
- Tracking blocker persistence across scans (same normalized node content → same `content_hash_id`)
- Identifying when blockers are resolved
- Deduplicating shared `messages` and `tags` rows across audits and scans

## GraphQL Access (Hasura)

Hasura provides GraphQL access with row-level security:

```graphql
query GetAuditBlockers($audit_id: uuid!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      blockers {
        id
        content
        url_id
        blocker_messages {
          message {
            content
            category
            message_tags {
              tag {
                content
              }
            }
          }
        }
      }
    }
  }
}
```

## Row-Level Security

Hasura defines two roles, `anonymous` and `user` (no `admin` role exists in Hasura). JWT claims carry the role and user ID:

```json
{
  "x-hasura-allowed-roles": ["user"],
  "x-hasura-default-role": "user",
  "x-hasura-user-id": "user-uuid",
  "x-hasura-org-id": "org-uuid"
}
```

Row-level ownership is **not** broadly enforced by Hasura permission filters — most tracked tables (including `audits`) have an open `{}` select filter for the `user` role. The one exception is `urls`, where the update permission filters on `user_id`; its delete permission is open. Ownership checks are instead primarily enforced at the Lambda layer, where handlers filter directly on the authenticated user's ID (`event.claims.sub`) in SQL queries. Admin-only endpoints (e.g. `getSystemStats`, `reviewAccessRequest`) are likewise enforced in the Lambda handler based on `users.type`, not via a Hasura role.

## Atomic Operations

Scan progress uses atomic PostgreSQL operations to prevent race conditions:

```sql
UPDATE "scans" 
SET 
  "processed_pages" = CASE 
    WHEN NOT (COALESCE("processed_pages", '[]'::jsonb) @> $1::jsonb)
    THEN COALESCE("processed_pages", '[]'::jsonb) || $1::jsonb
    ELSE "processed_pages"
  END
WHERE "id" = $2
RETURNING "pages", "processed_pages"
```

---
*For API usage examples, see the Backend API Reference.*
