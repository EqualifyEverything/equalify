import { db, event, formatId, isStaging } from '#src/utils'
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const saveAudit = async () => {
    try {
        const { auditName, scanFrequency, pages, saveAndRun, emailNotifications } = event.body;
        const scheduledAt = new Date();
        await db.connect();
        const id = (await db.query({
            text: `INSERT INTO "audits" ("user_id", "name", "interval", "scheduled_at", "status", "payload", "email_notifications")
                VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING "id"`,
            values: [event.claims.sub, auditName, scanFrequency, scheduledAt, saveAndRun ? 'new' : 'draft', JSON.stringify(event.body), emailNotifications],
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
            const urls = (await db.query({
                text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
                values: [id],
            })).rows;
            console.log('Found URLs for audit:', { auditId: id, count: urls?.length, urls });
            await lambda.send(new InvokeCommand({
                FunctionName: "aws-lambda-scan-sqs-router",
                InvocationType: "Event",
                Payload: JSON.stringify({
                    urls: urls?.map(url => ({ auditId: id, urlId: url.id, url: url.url, type: url.type }))
                })
            }));
            console.log('Scan jobs queued for audit:', id);
        }

        await db.clean();
        return { id: formatId(id) }
    }
    catch (err) {
        return { message: err?.detail ?? err }
    }
}