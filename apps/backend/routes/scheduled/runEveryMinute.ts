import { db, graphqlQuery } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const runEveryMinute = async () => {
    // Perform health check
    const response = await graphqlQuery({ query: `{users(limit:1){id}}` });
    if (!response?.users?.[0]?.id) {
        await fetch(process.env.SLACK_WEBHOOK, {
            method: 'POST',
            body: JSON.stringify({
                text: `*Equalify UIC* - Database connection failure detected`
            })
        })
    }

    // Determine whether we should run scheduled audits
    await db.connect();
    const scheduledAuditIds = (await db.query({
        text: `SELECT "id" FROM "audits" 
               WHERE 
                 EXTRACT(HOUR FROM "scheduled_at") = EXTRACT(HOUR FROM NOW())
                 AND EXTRACT(MINUTE FROM "scheduled_at") = EXTRACT(MINUTE FROM NOW())
                 AND (
                   ("interval" = 'Daily')
                   OR ("interval" = 'Weekly' AND EXTRACT(DOW FROM "scheduled_at") = EXTRACT(DOW FROM NOW()))
                   OR (
                     "interval" = 'Monthly' 
                     AND (
                       EXTRACT(DAY FROM "scheduled_at") = EXTRACT(DAY FROM NOW())
                       OR (
                         EXTRACT(DAY FROM "scheduled_at") >= 29
                         AND EXTRACT(DAY FROM NOW()) = EXTRACT(DAY FROM (DATE_TRUNC('MONTH', NOW()) + INTERVAL '1 MONTH - 1 DAY'))
                       )
                     )
                   )
                 )`,
    })).rows.map(obj => obj.id);
    for (const scheduledAuditId of scheduledAuditIds) {
        const scanId = (await db.query({
            text: `INSERT INTO "scans" ("audit_id", "status") VALUES ($1, $2) RETURNING "id"`,
            values: [scheduledAuditId, 'processing'],
        })).rows[0].id;
        const urls = (await db.query({
            text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
            values: [scheduledAuditId],
        })).rows;
        await lambda.send(new InvokeCommand({
            FunctionName: "aws-lambda-scan-sqs-router",
            InvocationType: "Event",
            Payload: JSON.stringify({
                urls: urls?.map(url => ({ auditId: scheduledAuditId, scanId: scanId, urlId: url.id, url: url.url, type: url.type }))
            })
        }));
        console.log('Scan jobs queued for audit:', scheduledAuditId);
    }

    // See if there are any "stuck" scans that we should error out!
    const stuckScans = (await db.query({
        text: `SELECT "id", "errors" FROM "scans" 
               WHERE "status" = 'processing' 
               AND (NOW() - "updated_at") > INTERVAL '60 minutes'`,
    })).rows;
    
    for (const scan of stuckScans) {
        const timeoutError = {
            type: 'scan_timeout',
            message: 'Scan timed out after 60 minutes',
            timestamp: new Date().toISOString(),
        };
        const updatedErrors = [...(scan.errors || []), timeoutError];
        await db.query({
            text: `UPDATE "scans" 
                   SET "status" = $1, "errors" = $2 
                   WHERE "id" = $3`,
            values: ['complete', updatedErrors, scan.id],
        });
        console.log('Marked stuck scan as complete:', scan.id);
    }

    await db.clean();
    return;
}