import { db, event, queueScanJobs } from '#src/utils';
import { syncAuditUrlsFromRemoteCsv } from '../internal';

export const rescanAudit = async () => {
    const { id: audit_id } = event.body;

    await db.connect();

    // sync remote CSV first so the scan runs against the fresh URL list (#628)
    await syncAuditUrlsFromRemoteCsv(audit_id);

    const urls = (await db.query({
        text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
        values: [audit_id],
    })).rows;
    console.log('Found URLs for audit:', { auditId: audit_id, count: urls?.length, urls });

    // Handle empty URLs case - create a complete scan immediately
    if (!urls || urls.length === 0) {
        console.log('No URLs found for audit, creating completed scan with no_urls error');
        const noUrlsError = {
            type: 'no_urls',
            message: 'No URLs configured for this audit. Please add URLs before scanning.',
            timestamp: new Date().toISOString(),
        };
        await db.query({
            text: `INSERT INTO "scans" ("audit_id", "status", "percentage", "pages", "processed_pages", "errors") VALUES ($1, $2, $3, $4, $5, $6)`,
            values: [audit_id, 'complete', 100, '[]', '[]', JSON.stringify([noUrlsError])],
        });
        await db.clean();
        return {
            status: 'success',
            message: 'No URLs to scan - audit has no URLs configured',
        };
    }

    const scanId = (await db.query({
        text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
        // `pages` snapshots the url ids too, so the stuck-scan sweep can tell exactly which queued pages never reported
        values: [audit_id, 'processing', JSON.stringify(urls.map(obj => ({ id: obj.id, url: obj.url, type: obj.type })))],
    })).rows[0].id;
    try {
        // Splits the URL list across async router invokes (1 MB payload cap each)
        await queueScanJobs({ auditId: audit_id, scanId, urls });
    } finally {
        await db.clean();
    }

    return {
        status: 'success',
        message: 'Scan successfully queued',
    };
}
