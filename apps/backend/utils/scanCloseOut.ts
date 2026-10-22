import { db } from './db';

// Cap on per-URL error entries a single close-out adds to scans.errors. Every
// scans row (errors included) is re-fetched by the audit page's 2-second poll
// while a scan runs and by getAuditChart, so an uncapped list for a 6,000-URL
// audit would add roughly 1 MB to each of those responses. The summary entry
// always carries the full counts.
export const MAX_URL_ERROR_ENTRIES = 500;

export interface ScanErrorEntry {
    type: string;
    message: string;
    urlId?: string;
    url?: string;
    timestamp?: string;
    details?: Record<string, unknown>;
}

interface CloseOutArgs {
    scanId: string;
    urls: { id: string; url: string }[];
    /** Error type written on each per-URL entry, e.g. 'url_timeout' */
    type: string;
    /** Message written on each per-URL entry */
    message: string;
}

/**
 * Records a terminal error for URLs that will never report back through
 * scanWebhook and counts them as processed, mirroring what scanWebhook does for
 * a URL whose scan failed, so the scan's progress can reach 100%. Status,
 * percentage and the denormalized counts are left to finalizeScan.
 */
export const closeOutScanUrls = async ({ scanId, urls, type, message }: CloseOutArgs) => {
    if (urls.length === 0) return;
    const timestamp = new Date().toISOString();
    const entries: ScanErrorEntry[] = urls
        .slice(0, MAX_URL_ERROR_ENTRIES)
        .map((u) => ({ type, message, urlId: u.id, url: u.url, timestamp }));

    await db.query({
        text: `UPDATE "scans"
               SET "errors" = COALESCE("errors", '[]'::jsonb) || $1::jsonb,
                   "processed_pages" = (
                       SELECT COALESCE(jsonb_agg(DISTINCT p), '[]'::jsonb)
                       FROM jsonb_array_elements(COALESCE("processed_pages", '[]'::jsonb) || $2::jsonb) p
                   )
               WHERE "id" = $3`,
        values: [JSON.stringify(entries), JSON.stringify(urls.map((u) => u.id)), scanId],
    });
};

/**
 * Marks a scan complete and freezes its rolled-up numbers: percentage from
 * processed vs. total pages, blocker_count/equalified_count from the blockers
 * table (the chart and summary endpoints read those instead of aggregating
 * live). The parent audit flips to 'complete' when the scan found anything and
 * 'failed' otherwise, as scanWebhook does when the last page comes in - but only
 * if this is still the audit's newest scan.
 */
export const finalizeScan = async ({ scanId, auditId }: { scanId: string; auditId: string }) => {
    const row = (await db.query({
        text: `UPDATE "scans"
               SET "status" = 'complete',
                   "percentage" = CASE
                       WHEN jsonb_array_length(COALESCE("pages", '[]'::jsonb)) > 0
                       THEN LEAST(100, ROUND(100.0 * jsonb_array_length(COALESCE("processed_pages", '[]'::jsonb))
                                                   / jsonb_array_length(COALESCE("pages", '[]'::jsonb))))
                       ELSE 0
                   END,
                   "blocker_count" = COALESCE((SELECT COUNT(*) FROM "blockers" WHERE "scan_id" = $1), 0),
                   "equalified_count" = COALESCE((SELECT COUNT(*) FROM "blockers" WHERE "scan_id" = $1 AND "equalified" = true), 0)
               WHERE "id" = $1
               RETURNING "blocker_count", "percentage"`,
        values: [scanId],
    })).rows[0];

    // Only the audit's newest scan speaks for the audit's status: closing out an
    // older stuck scan while a newer one is running must not clobber it.
    const hasSuccessfulPages = Number(row?.blocker_count ?? 0) > 0;
    await db.query({
        text: `UPDATE "audits" a SET "status" = $1
               WHERE a."id" = $2
               AND NOT EXISTS (
                   SELECT 1 FROM "scans" n
                   WHERE n."audit_id" = a."id"
                   AND n."created_at" > (SELECT "created_at" FROM "scans" WHERE "id" = $3)
               )`,
        values: [hasSuccessfulPages ? 'complete' : 'failed', auditId, scanId],
    });

    return { blockerCount: Number(row?.blocker_count ?? 0), percentage: Number(row?.percentage ?? 0) };
};
