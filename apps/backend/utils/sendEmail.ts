import { SES } from '@aws-sdk/client-ses';
import { formatEmail } from './formatEmail';
const ses = new SES({ region: process.env.AWS_REGION ?? "us-east-2" });

export const sendEmail = async ({ to, subject, body }) => {
    try {
        await ses.sendEmail({
            Destination: { ToAddresses: [to] },
            Source: process.env.SES_ADMIN_EMAIL ?? `noreply@equalify.uic.edu`,
            Message: {
                Subject: { Data: subject },
                Body: { Html: { Data: formatEmail({ body }) } },
            },
        });
    }
    catch (err) { console.log(err); }
}