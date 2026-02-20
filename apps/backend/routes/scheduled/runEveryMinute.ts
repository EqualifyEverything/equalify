import { db, graphqlQuery } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const runEveryMinute = async () => {
    // Perform health check
    try {
        const response = await graphqlQuery({ query: `{users(limit:1){id}}` });
        if (!response?.users?.[0]?.id) {
            await fetch(process.env.SLACK_WEBHOOK, {
                method: 'POST',
                body: JSON.stringify({
                    text: `*Equalify UIC* - Database connection failure detected`
                })
            })
        }
    } catch (healthCheckError) {
        console.error('Health check failed:', healthCheckError);
    }

    // Determine whether we should run scheduled audits
    await db.connect();
    try {
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
            const urls = (await db.query({
                text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
                values: [scheduledAuditId],
            })).rows;

            // Skip scheduled audits with no URLs to prevent hung scans
            if (!urls || urls.length === 0) {
                console.log('Skipping scheduled audit with no URLs:', scheduledAuditId);
                continue;
            }

            const scanId = (await db.query({
                text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
                values: [scheduledAuditId, 'processing', JSON.stringify(urls.map(obj => ({ url: obj.url, type: obj.type })))],
            })).rows[0].id;
            await lambda.send(new InvokeCommand({
                FunctionName: "aws-lambda-scan-sqs-router",
                InvocationType: "Event",
                Payload: JSON.stringify({
                    urls: urls?.map(url => ({ auditId: scheduledAuditId, scanId: scanId, urlId: url.id, url: url.url, type: url.type }))
                })
            }));
            console.log('Scan jobs queued for audit:', scheduledAuditId);
        }
    } catch (scheduledAuditError) {
        console.error('Scheduled audit processing failed:', scheduledAuditError);
    }

    // See if there are any "stuck" scans that we should error out!
    const stuckScans = (await db.query({
        text: `SELECT s."id", s."errors", s."audit_id" FROM "scans" s
               WHERE s."status" = 'processing' 
               AND (NOW() - s."updated_at") > INTERVAL '15 minutes'`,
    })).rows;
    
    for (const scan of stuckScans) {
        const timeoutError = {
            type: 'scan_timeout',
            message: 'Scan timed out after 15 minutes of inactivity',
            timestamp: new Date().toISOString(),
        };
        const updatedErrors = [...(scan.errors || []), timeoutError];
        await db.query({
            text: `UPDATE "scans" 
                   SET "status" = $1, "errors" = $2 
                   WHERE "id" = $3`,
            values: ['complete', updatedErrors, scan.id],
        });

        // Also update the parent audit so the frontend stops showing the spinner
        if (scan.audit_id) {
            const hasSuccessfulPages = (await db.query({
                text: `SELECT COUNT(*) FROM "blockers" WHERE "scan_id"=$1`,
                values: [scan.id],
            })).rows[0].count > 0;

            await db.query({
                text: `UPDATE "audits" SET "status" = $1 WHERE "id" = $2 AND "status" NOT IN ('complete', 'failed')`,
                values: [hasSuccessfulPages ? 'complete' : 'failed', scan.audit_id],
            });
        }

        console.log('Marked stuck scan as complete:', scan.id, 'audit:', scan.audit_id);
    }

    await db.clean();
    return;
}