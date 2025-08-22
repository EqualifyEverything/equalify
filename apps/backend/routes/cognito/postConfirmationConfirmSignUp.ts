import { event, db, cognito } from "#src/utils";
import { randomUUID } from 'crypto'

export const postConfirmationConfirmSignUp = async () => {
    const { sub, email, name, website } = event.request.userAttributes;

    if (website === 'api-flow') {
        return event;
    }

    await db.connect();

    const organizationId = randomUUID();
    await cognito.adminUpdateUserAttributes({
        UserAttributes: [{
            Name: 'profile',
            Value: organizationId
        }],
        UserPoolId: process.env.USER_POOL_ID,
        Username: email
    });


    await db.query({
        text: `INSERT INTO "organizations" ("id", "name") VALUES ($1, $2) ON CONFLICT DO NOTHING`,
        values: [organizationId, `${name}'s Organization`],
    });
    await db.query({
        text: `INSERT INTO "users" ("id", "email", "name", "organization_id") VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING`,
        values: [sub, email, name ?? 'User', organizationId],
    });

    await db.clean();
    return event;
}