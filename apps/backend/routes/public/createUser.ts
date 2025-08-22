import { cognito, db, event } from '#src/utils'
import { randomUUID } from 'crypto'

export const createUser = async () => {
    const { name, email, password } = event.body;
    try {
        const username = randomUUID();
        const organizationId = randomUUID();
        const { User } = await cognito.adminCreateUser({
            UserPoolId: process.env.USER_POOL_ID,
            Username: username,
            MessageAction: 'SUPPRESS',
            UserAttributes: [
                { Name: 'email', Value: email },
                { Name: 'email_verified', Value: 'true' },
                { Name: 'name', Value: name },
                { Name: 'profile', Value: organizationId },
                { Name: 'website', Value: 'api-flow' },
            ],
        });
        await cognito.adminSetUserPassword({
            UserPoolId: process.env.USER_POOL_ID,
            Username: email,
            Permanent: true,
            Password: password,
        });
        const sub = User.Attributes.find(obj => obj.Name === 'sub')?.Value;
        await db.connect();
        await db.query({
            text: `INSERT INTO "organizations" ("id", "name") VALUES ($1, $2) ON CONFLICT DO NOTHING`,
            values: [organizationId, `${name}'s Organization`],
        });
        await db.query({
            text: `INSERT INTO "users" ("id", "email", "name", "organization_id") VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING`,
            values: [sub, email, name ?? 'User', organizationId],
        });
        await db.clean();

        const authResponse = await cognito.adminInitiateAuth({
            UserPoolId: process.env.USER_POOL_ID,
            ClientId: process.env.WEB_CLIENT_ID,
            AuthFlow: 'ADMIN_USER_PASSWORD_AUTH',
            AuthParameters: {
                USERNAME: email,
                PASSWORD: password,
            },
        });
        const accessToken = authResponse.AuthenticationResult.IdToken;

        return { accessToken };
    }
    catch (err) {
        return { message: err }
    }
}