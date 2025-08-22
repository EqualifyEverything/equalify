import { db, event, isStaging } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const runScan = async () => {
    const { audit_id } = event.body;
    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id"=$1`,
        values: [audit_id],
    })).rows?.[0];
    try {
        const scanResponse = await (await fetch(`https://scan${isStaging ? '-dev' : ''}.equalify.app/generate/${audit?.is_sitemap ? 'sitemapurl' : 'url'}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: audit.audit_url, userId: event.claims.sub })
        })).json();
        // console.log(JSON.stringify({ scanResponse }));

        for (const { jobId, url } of scanResponse?.jobs ?? []) {
            const urlId = (await db.query({
                text: `SELECT "id" FROM "urls" WHERE "organization_id"=$1 AND "url"=$2 AND "audit_id"=$3`,
                values: [event.claims.profile, url, audit_id],
            })).rows?.[0]?.id ?? (await db.query({
                text: `INSERT INTO "urls" ("organization_id", "url", "audit_id") VALUES ($1, $2, $3) RETURNING "id"`,
                values: [event.claims.profile, url, audit_id]
            })).rows?.[0]?.id;

            const scan = (await db.query({
                text: `INSERT INTO "scans" ("user_id", "organization_id", "audit_id", "url_id", "job_id") VALUES ($1, $2, $3, $4, $5) RETURNING "job_id"`,
                values: [event.claims.sub, event.claims.profile, audit_id, urlId, parseInt(jobId)]
            })).rows[0];
        }

        lambda.send(new InvokeCommand({
            FunctionName: `equalifyv2-api${isStaging ? '-staging' : ''}`,
            InvocationType: "Event",
            Payload: Buffer.from(JSON.stringify({
                path: '/internal/processScans',
                organization_id: event.claims.profile,
                audit_id: audit_id,
                is_sitemap: audit?.is_sitemap,
            })),
        }));
    }
    catch (err) {
        console.log(err);
    }
    await db.clean();

    return {
        status: 'success',
        message: 'Scan successfully queued',
    };
}
