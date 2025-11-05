import { db, event } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const rescanAudit = async () => {
    const { id: audit_id } = event.body;

    await db.connect();
    const urls = (await db.query({
        text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
        values: [audit_id],
    })).rows;
    console.log('Found URLs for audit:', { auditId: audit_id, count: urls?.length, urls });
    const scanId = (await db.query({
        text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
        values: [audit_id, 'processing', JSON.stringify(urls.map(obj => ({ url: obj.url, type: obj.type })))],
    })).rows[0].id;
    await lambda.send(new InvokeCommand({
        FunctionName: "aws-lambda-scan-sqs-router",
        InvocationType: "Event",
        Payload: JSON.stringify({
            urls: urls?.map(url => ({ auditId: audit_id, scanId: scanId, urlId: url.id, url: url.url, type: url.type }))
        })
    }));
    await db.clean();

    return {
        status: 'success',
        message: 'Scan successfully queued',
    };
}
