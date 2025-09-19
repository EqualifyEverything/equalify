import { SES } from '@aws-sdk/client-ses';
import { formatEmail } from './formatEmail';
const ses = new SES({ region: 'us-east-2' });

export const sendEmail = async ({ to, subject, body }) => {
    try {
        await ses.sendEmail({
            Destination: { ToAddresses: [to] },
            Source: `noreply@${process.env.URL}`,
            Message: {
                Subject: { Data: subject },
                Body: { Html: { Data: formatEmail(body) } },
            },
        });
    }
    catch (err) { console.log(err); }
}