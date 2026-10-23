import { db, event, formatId, queueScanJobs } from '#src/utils'

export const saveAudit = async () => {
    try {
        const { auditName, scanFrequency, pages, saveAndRun, emailNotifications, remoteCsvUrl } = event.body;
        const scheduledAt = new Date();
        await db.connect();
        const id = (await db.query({
            text: `INSERT INTO "audits" ("user_id", "name", "interval", "scheduled_at", "status", "payload", "email_notifications", "remote_csv_url")
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING "id"`,
            values: [event.claims.sub, auditName, scanFrequency, scheduledAt, saveAndRun ? 'new' : 'draft', JSON.stringify(event.body), emailNotifications, remoteCsvUrl],
        })).rows[0].id;

        // Insert all URLs in a single query using UNNEST for clarity
        // Pages look like this: [{"url": "equalify.app","type": "html"},{"url": "equalify.app/about","type": "html"}]
        console.log('Inserting pages:', { count: pages?.length, pages });
        if (pages?.length) {
            const userIds = pages.map(() => event.claims.sub);
            const auditIds = pages.map(() => id);
            const urls = pages.map(page => page.url);
            const types = pages.map(page => page.type);

            await db.query({
                text: `INSERT INTO "urls" ("user_id", "audit_id", "url", "type") 
                       SELECT * FROM UNNEST($1::uuid[], $2::uuid[], $3::text[], $4::text[])`,
                values: [userIds, auditIds, urls, types],
            });
            console.log('Successfully inserted URLs');
        }

        if (saveAndRun) {
            // Check if there are pages to scan first
            if (!pages || pages.length === 0) {
                console.log('No pages to scan for audit, skipping scan creation');
            } else {
                const urls = (await db.query({
                    text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
                    values: [id],
                })).rows;
                console.log('Found URLs for audit:', { auditId: id, count: urls?.length, urls });
                // `pages` snapshots the url ids too, so the stuck-scan sweep can tell exactly which queued pages never reported
                const scanId = (await db.query({
                    text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
                    values: [id, 'processing', JSON.stringify(urls.map(u => ({ id: u.id, url: u.url, type: u.type })))],
                })).rows[0].id;
                // Splits the URL list across async router invokes (1 MB payload cap each)
                await queueScanJobs({ auditId: id, scanId, urls });
                console.log('Scan jobs queued for audit:', id);
            }
        }

        await db.clean();
        return { id: formatId(id) }
    }
    catch (err) {
        return { message: err?.detail ?? err }
    }
}