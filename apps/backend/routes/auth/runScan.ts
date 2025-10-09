import { db, event } from '#src/utils';
import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
const lambda = new LambdaClient();

export const runScan = async () => {
    const { audit_id } = event.body;
    await db.connect();
    const urls = (await db.query({
        text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
        values: [audit_id],
    })).rows;
    const response = await lambda.send(new InvokeCommand({
        FunctionName: "aws-lambda-scan-sqs-router",
        InvocationType: "RequestResponse",
        Payload: JSON.stringify({
            urls: urls?.map(url => ({ auditId: audit_id, urlId: url.id, url: url.url, type: url.type }))
        })
    }));
    const responsePayload = JSON.parse(new TextDecoder().decode(response.Payload));
    console.log(responsePayload);
    await db.clean();

    return {
        status: 'success',
        message: 'Scan successfully queued',
    };
}
