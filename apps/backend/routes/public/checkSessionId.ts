import { db, event, stripe } from '#src/utils';

export const checkSessionId = async () => {
    const session = await stripe.checkout.sessions.retrieve(event.body.session_id);
    if (new Date().getTime() < new Date(session?.expires_at * 1000).getTime()) {
        const customerId = session?.customer;
        await db.connect();
        const userSignedIn = (await db.query({
            text: `SELECT "signed_in_once" FROM "users" WHERE "customer_id"=$1`,
            values: [customerId],
        }))?.rows?.[0]?.signed_in_once;
        if (!userSignedIn) {
            await db.query({
                text: `UPDATE "users" SET "signed_in_once"=$1 WHERE "customer_id"=$2`,
                values: [true, customerId],
            });
            await db.clean();
            return {
                email: session?.customer_details?.email,
                password: session?.custom_fields?.[0]?.text?.value,
            };
        }
        else {
            await db.clean();
            return {
                email: session?.customer_details?.email,
                password: null,
            };
        }
    }
}