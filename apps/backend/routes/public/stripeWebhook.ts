import { randomUUID } from 'crypto'
import { cm, cognito, db, event, slack, sleep, stripe } from '#src/utils';

export const stripeWebhook = async () => {
    try {
        const stripeEvent = stripe.webhooks.constructEvent(event.rawBody, event.headers['stripe-signature'], process.env.STRIPE_SIGNING_SECRET);
        console.log(JSON.stringify({ stripeEvent }));

        if (stripeEvent.type === 'checkout.session.completed') {
            await sleep(1000);
            const customerId = stripeEvent.data.object.customer;
            const email = stripeEvent.data.object.customer_details.email;
            const name = stripeEvent.data.object.customer_details.name;
            const password = stripeEvent.data.object.custom_fields?.find(obj => obj.key === 'password')?.text?.value;
            const username = randomUUID();

            const { User } = await cognito.adminCreateUser({
                UserPoolId: process.env.USER_POOL_ID,
                Username: username,
                MessageAction: 'SUPPRESS',
                UserAttributes: [
                    { Name: 'email', Value: email },
                    { Name: 'email_verified', Value: 'true' },
                    { Name: 'name', Value: name },
                    { Name: 'profile', Value: customerId },
                ],
            });
            const newSub = User.Attributes.find(obj => obj.Name === 'sub')?.Value?.replaceAll('-', '');
            await db.connect();
            await db.query({
                text: `INSERT INTO "users" ("id", "email", "name", "customer_id") VALUES ($1, $2, $3, $4)`,
                values: [newSub, email, name, customerId],
            })
            await db.clean();
            await cognito.adminSetUserPassword({
                UserPoolId: process.env.USER_POOL_ID,
                Username: email,
                Permanent: true,
                Password: password,
            });

            await cm.sendSmartEmail({
                to: email,
                data: {
                    subject: `Welcome to Equalify!`,
                    body: `<p>Hey there,</p><p>Welcome to Equalify! Log in using your email and password to start running accessibility audits.</p>
                        <p>Thanks,<br/>Equalify</p>`
                },
            })
            await slack(`${email} (${name}) just signed up for Equalify`);
        }

        return {
            message: `Success`,
            type: stripeEvent.type,
        };
    } catch (err) {
        console.log(`Stripe verification error`);
        console.log(err);

        return {
            message: `Stripe verification error`,
            error: err.message,
        }
    }
}