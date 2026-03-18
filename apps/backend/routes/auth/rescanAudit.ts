import { db, event, isStaging } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
import { syncAuditUrlsFromRemoteCsv } from '../internal';
const lambda = new LambdaClient();

export const rescanAudit = async () => {
    const { id: audit_id } = event.body;

    await db.connect();
    const urls = (await db.query({
        text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
        values: [audit_id],
    })).rows;
    console.log('Found URLs for audit:', { auditId: audit_id, count: urls?.length, urls });

    // hook to check for remote CSV
    await syncAuditUrlsFromRemoteCsv(audit_id);

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
        values: [audit_id, 'processing', JSON.stringify(urls.map(obj => ({ url: obj.url, type: obj.type })))],
    })).rows[0].id;
    await lambda.send(new InvokeCommand({
        FunctionName: "aws-lambda-scan-sqs-router",
        InvocationType: "Event",
        Payload: JSON.stringify({
            urls: urls?.map(url => ({ auditId: audit_id, scanId: scanId, urlId: url.id, url: url.url, type: url.type, isStaging }))
        })
    }));
    await db.clean();

    return {
        status: 'success',
        message: 'Scan successfully queued',
    };
}
