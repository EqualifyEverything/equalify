import { cognito, event } from '#src/utils'

export const authenticate = async () => {
    const { email, password } = event.body;
    try {
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