# Scanning Services

Equalify's accessibility scanning is performed by a collection of microservices that work together to scan HTML pages and PDF documents at scale.

## How Equalify Scans: URL-Based vs Crawling

Many accessibility tools (such as SiteImprove, Pope Tech, or Monsido) use a **crawling** approach: you provide a root domain, and the tool automatically discovers pages by following links, parsing sitemaps, and spidering the site. While convenient, crawling has notable drawbacks:

- **Unpredictable scope**: Crawlers may miss pages behind JavaScript navigation or follow links to external sites, leading to incomplete or noisy results.
- **Slow discovery**: A full crawl of a large site can take hours before scanning even begins.
- **No PDF coverage**: Most crawlers focus on HTML pages and ignore linked PDF documents.
- **Redundant scans**: Crawlers often re-scan unchanged pages, wasting resources.

Equalify takes a **URL-based** approach instead. Users provide an explicit list of URLs — either entered manually or uploaded via CSV — and Equalify scans exactly those pages. This design offers several advantages:

| | Crawling Tools | Equalify (URL-Based) |
|---|---|---|
| **Scope** | Automatic discovery; may miss or over-include pages | Explicit — you choose exactly what's scanned |
| **PDF support** | Typically limited or absent | First-class PDF scanning via veraPDF |
| **Speed** | Crawl + scan (slow for large sites) | Scan only (no discovery overhead) |
| **Repeatability** | Results vary based on crawl path | Deterministic — same URLs every time |
| **Gated content** | Requires authenticated crawling setup | Scan any publicly accessible URL directly |

By default, Equalify does not continuously discover pages on your site the way a spidering crawler does — if you add a new page, you need to add its URL to your audit. Equalify does offer an opt-in "Crawl Site" helper that fetches a site's `sitemap.xml` and lets you review and select which discovered URLs to add — a single, explicit lookup rather than link-following/spidering. The trade-off is still full control over what gets scanned and consistent, reproducible results across scan runs.

> **Tip**: For large sites, use the CSV upload feature to bulk-add URLs. You can export a URL list from your CMS, sitemap.xml, or analytics tool and import it directly into an Equalify audit. Equalify also supports syncing an audit's URLs from a hosted remote CSV — for WordPress sites, the official [Equalify WordPress Integration plugin](https://github.com/EqualifyEverything/equalify-wp-integration) generates this CSV feed automatically, keeping an audit's pages, posts, and PDF library in sync with the site as content changes.

## Overview

The scanning architecture uses AWS SQS FIFO queues to distribute work across scanner Lambdas, ensuring reliable delivery and ordered processing per audit.

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐     ┌───────────┐
│   Backend   │────▶│  SQS Router  │────▶│  SQS Queue  │────▶│  Scanner  │
│     API     │     │    Lambda    │     │   (FIFO)    │     │  Lambda   │
└─────────────┘     └──────────────┘     └─────────────┘     └───────────┘
                                                                    │
                                                                    ▼
                                                             ┌───────────┐
                                                             │  Webhook  │
                                                             │ (Backend) │
                                                             └───────────┘
```

## Service Components

### SQS Router (`aws-lambda-scan-sqs-router`)

**Purpose**: Receives scan requests and routes URLs to appropriate SQS queues based on content type.

**Input Schema** (Zod validated):
```typescript
const scansSchema = z.object({
  urls: z.array(
    z.object({
      auditId: z.string(),
      scanId: z.string(),
      urlId: z.string(),
      url: z.string(),
      type: z.string(),      // "html" or "pdf"
      isStaging: z.boolean().optional(),
    })
  ),
});
```

**Behavior**:
1. Parse and validate incoming URL list
2. Separate URLs by type (HTML vs PDF)
3. Batch URLs into groups of 10 (SQS limit)
4. Send batches to appropriate FIFO queues

**Queue Configuration**:
- HTML Queue: `scanHtml.fifo` (Terraform-provisioned deployments use a project/environment-prefixed name)
- PDF Queue: `scanPdf.fifo` (same prefixing applies)
- Message Group ID: `auditId` (ensures ordered processing per audit)
- Deduplication ID: `${scanId}-${urlId}` (prevents duplicate scans)
- Both queues have a dead-letter queue with a 14-day retention

### Crawler (`aws-lambda-crawler`)

**Purpose**: Optional URL discovery helper, invoked directly from the backend API (`crawlUrl` endpoint) — not part of the SQS scan pipeline.

**Behavior**:
1. Receives a single URL
2. Attempts to fetch `<url>/sitemap.xml` and parse it for page URLs
3. Falls back to returning just the input URL if no sitemap is found
4. Returns the discovered URL list to the frontend, where the user reviews and selects which URLs to add to the audit

### HTML Scanner (`aws-lambda-scan-html`)

**Purpose**: Scans web pages for accessibility issues using a headless Chromium browser and axe-core.

**Key Features**:
- Uses AWS Lambda Powertools for metrics and logging
- Processes SQS records with partial batch failure handling
- 2-minute timeout per scan to prevent Lambda hangs
- Runs axe-core with two custom rules in addition to the default ruleset: an empty-alt-tag check and a PDF-link check (flags links to PDF documents for review)
- Converts axe-core results to EqualifyV2 format

**Scan Flow**:
1. Receive SQS message with URL details
2. Launch headless Chromium browser
3. Navigate to URL and wait for page load
4. Inject and run axe-core accessibility tests
5. Convert results to EqualifyV2 format
6. POST results to webhook endpoint

**Error Handling**:
- Timeout errors: Logged and reported as failures
- Network errors: Caught and reported with error type
- Partial failures: Uses AWS Powertools batch processor

### PDF Scanner (`aws-lambda-scan-pdf`)

**Purpose**: Orchestrates PDF accessibility scanning using veraPDF.

**Architecture**:
- TypeScript Lambda receives SQS message
- Invokes Java-based veraPDF Lambda
- Converts veraPDF results to EqualifyV2 format
- Reports results via webhook

### veraPDF Interface (`aws-lambda-verapdf-interface`)

**Purpose**: Java Lambda that performs actual PDF scanning using veraPDF library.

**Features**:
- Native Java Lambda for performance
- Downloads PDF from URL
- Executes veraPDF accessibility checks
- Returns raw JSON results

## Result Format (EqualifyV2)

All scanners convert their results to a unified format:

```typescript
interface StreamResults {
  status: string;       // "complete" or "failed"
  auditId: string;
  scanId: string;
  urlId: string;
  url?: string;
  blockers: Blocker[];
  date: string;
  message: string;
}

interface Blocker {
  source: string;         // "axe-core" | "pdf-scan" (editoria11y is present in the type but currently unused/disabled)
  test: string;           // Rule ID (e.g., "color-contrast")
  tags?: string[] | null; // WCAG tags (e.g., ["wcag2aa", "wcag143"])
  description: string;    // Human-readable rule description
  summary: string;        // Specific failure details
  node: string | null;    // HTML snippet or null for PDFs
}
```

## Axe-Core Conversion

`shared/convertors/AxeToEqualify2.ts` exports `convertToEqualifyV2`, which transforms axe-core results:

```typescript
function convertToEqualifyV2(axeResult: AxeResults, job: any): StreamResults {
  const blockers: Blocker[] = [];

  // Process violations and incomplete results
  [axeResult.incomplete, axeResult.violations].forEach(results => {
    results?.forEach(rule => {
      rule.nodes?.forEach(node => {
        blockers.push({
          source: "axe-core",
          test: rule.id,
          tags: rule.tags,
          description: `${rule.description}. ${rule.help}`,
          summary: node.failureSummary ?? "",
          node: node.html
        });
      });
    });
  });

  return {
    auditId: job.auditId,
    scanId: job.scanId,
    urlId: job.urlId,
    url: job.url,
    blockers,
    date: axeResult.timestamp,
    status: "complete",
    message: "",
  };
}
```

## Webhook Integration

Results are sent to the backend API webhook:

| Environment | Endpoint |
|-------------|----------|
| Production | `https://api.equalifyapp.com/public/scanWebhook` |
| Staging | `https://api-staging.equalifyapp.com/public/scanWebhook` |

**Webhook Payload**:
```json
{
  "auditId": "uuid",
  "scanId": "uuid",
  "urlId": "uuid",
  "url": "https://example.com",
  "status": "complete",
  "date": "2026-08-17T00:00:00.000Z",
  "message": "",
  "blockers": [...]
}
```

## Metrics & Monitoring

Using AWS Lambda Powertools:

- `scansStarted`: Count of scans initiated (HTML/PDF scanner Lambdas)
- `ScanDuration`: Time to complete each scan (milliseconds)
- `scanRequest`: Count of scan requests received by the SQS Router
- Cold start metrics captured automatically

## Adding New Scanners

To add a new scanner type:

1. Create a new service in `services/`
2. Implement SQS message consumption
3. Create a converter in `shared/convertors/`
4. Add queue routing in `aws-lambda-scan-sqs-router`
5. Register the new type in the scan schema

---
*For database schema details, see the Database Schema guide.*
