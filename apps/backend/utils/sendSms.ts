import { SNSClient, PublishCommand } from "@aws-sdk/client-sns";
export const snsClient = new SNSClient({ region: 'us-east-1' });

export const sendSms = async ({ message, phone }) => {
    try {
        await snsClient.send(
            new PublishCommand({
                Message: message,
                PhoneNumber: phone,
                MessageAttributes: {
                    'AWS.MM.SMS.OriginationNumber': {
                        DataType: 'String',
                        StringValue: process.env.PHONE,
                    },
                },
            })
        );
    }
    catch (err) { console.log(err) }
}