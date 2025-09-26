import { LambdaClient, InvokeCommand } from "@aws-sdk/client-lambda";
import { randomUUID } from "crypto";
const lambdaClient = new LambdaClient({ region: 'us-east-2' });

export const testScan = async () => {
    console.log(`Test scan!`);
    const auditId = randomUUID();
    const response = await lambdaClient.send(new InvokeCommand({
        FunctionName: "aws-lambda-scan-sqs-router",
        InvocationType: "RequestResponse",
        Payload: JSON.stringify({
            urls: [
                { auditId: auditId, urlId: randomUUID(), url: 'https://equalify.app', type: 'html' },
            ],
        })
    }));
    const responsePayload = JSON.parse(new TextDecoder().decode(response.Payload));
    console.log(responsePayload);
    return;
}