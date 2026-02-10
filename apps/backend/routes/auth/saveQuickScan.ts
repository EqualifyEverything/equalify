import { db, event, formatId, isStaging } from '#src/utils'
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const saveQuickScan = async () => {
    try {
        const { url, type } = event.body;

        if (!url || !['html', 'pdf'].includes(type)) {
            return { statusCode: 400, body: { message: 'URL and type (html/pdf) are required' } };
        }

        const scheduledAt = new Date();
        await db.connect();

        const auditName = `Quick Scan: ${url}`;
        const id = (await db.query({
            text: `INSERT INTO "audits" ("user_id", "name", "interval", "scheduled_at", "status", "payload", "email_notifications")
                VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING "id"`,
            values: [
                event.claims.sub,
                auditName,
                'Quick Scan',
                scheduledAt,
                'new',
                JSON.stringify({ url, type }),
                JSON.stringify({ emails: [] }),
            ],
        })).rows[0].id;

        const urlRow = (await db.query({
            text: `INSERT INTO "urls" ("user_id", "audit_id", "url", "type") VALUES ($1, $2, $3, $4) RETURNING "id"`,
            values: [event.claims.sub, id, url, type],
        })).rows[0];

        const scanId = (await db.query({
            text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
            values: [id, 'processing', JSON.stringify([{ url, type }])],
        })).rows[0].id;
        await lambda.send(new InvokeCommand({
            FunctionName: "aws-lambda-scan-sqs-router",
            InvocationType: "Event",
            Payload: JSON.stringify({
                urls: [{ auditId: id, scanId, urlId: urlRow.id, url, type, isStaging }]
            })
        }));

        console.log('Quick scan queued:', { auditId: id, scanId, url, type });

        await db.clean();
        return { id: formatId(id) };
    }
    catch (err) {
        return { message: err?.detail ?? err };
    }
}
