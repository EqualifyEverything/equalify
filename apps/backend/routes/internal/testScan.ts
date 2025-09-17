import { LambdaClient, InvokeCommand } from "@aws-sdk/client-lambda";
import { randomUUID } from "crypto";
const lambdaClient = new LambdaClient({ region: 'us-east-2' });

export const testScan = async () => {
    console.log(`Test scan!`);
    const response = await lambdaClient.send(new InvokeCommand({
        FunctionName: "aws-lambda-scan-sqs-router",
        InvocationType: "RequestResponse",
        Payload: JSON.stringify({
            urls: [
                { id: randomUUID(), url: 'https://equalify.app', type: 'html' },
            ],
            webhookUrl: 'https://api-staging.equalifyapp.com/public/scanWebhook',
        })
    }));
    const responsePayload = JSON.parse(new TextDecoder().decode(response.Payload));
    console.log(responsePayload);
    return;
}