import { db, cognito, event } from "#src/utils";

export const updateUser = async () => {
    await db.connect();
    const unverifiedAttributes = ['email', 'phone_number'];
    const verifiedAttributes = [];
    const isSsoUser = !event.claims['cognito:username']; // SSO users don't have cognito:username

    console.log(event.claims);

    // First check if we're updating the email or phone number (Cognito users only)
    if (!isSsoUser) {
        for (const unverifiedAttribute of unverifiedAttributes) {
            if (Object.keys(event.body).includes(unverifiedAttribute) && event.claims[unverifiedAttribute] !== event.body[unverifiedAttribute]) {
                try {
                    await cognito.adminUpdateUserAttributes({
                        UserAttributes: [{ Name: unverifiedAttribute, Value: event.body[unverifiedAttribute] }],
                        UserPoolId: process.env.USER_POOL_ID,
                        Username: event.claims['cognito:username']
                    });
                    if (event.body[unverifiedAttribute].length) {
                        verifiedAttributes.push(unverifiedAttribute.replace('_', ' '));
                    }
                }
                catch (err) {
                    return {
                        statusCode: 400,
                        body: JSON.stringify(`There was an error updating your ${unverifiedAttribute}`)
                    }
                }
                if (event.body[unverifiedAttribute].length) {
                    delete event.body[unverifiedAttribute];
                }
            }
        }
    } else {
        // For SSO users, email/phone changes are managed by their SSO provider
        // Remove these attributes from the update
        for (const attr of unverifiedAttributes) {
            if (Object.keys(event.body).includes(attr)) {
                delete event.body[attr];
            }
        }
    }

    for (const [key, value] of Object.entries(event.body)) {
        await db.query({
            text: `UPDATE "users" SET "${key}"=$1 WHERE "id"=$2`,
            values: [value, event.claims.sub]
        });
    }

    await db.clean();
    return JSON.stringify(`Success!${verifiedAttributes.length ? ` Please verify your new ${verifiedAttributes.join(',')} in order to finish updating your profile` : ''}`);
}