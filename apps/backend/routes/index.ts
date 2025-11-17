import { router, event, setEvent, logEvent, verifySsoToken, ensureSsoUser } from '#src/utils';
import * as authRoutes from "./auth";
import { postConfirmationConfirmSignUp, preSignUpSignUp, tokenGeneration } from "./cognito";
import * as internalRoutes from "./internal";
import * as publicRoutes from "./public";
import * as scheduledRoutes from "./scheduled";
import * as hasuraRoutes from "./hasura";
import { CognitoJwtVerifier } from "aws-jwt-verify";
const verifier = CognitoJwtVerifier.create({ userPoolId: process.env.USER_POOL_ID, tokenUse: "id", clientId: process.env.WEB_CLIENT_ID });

export const authRouter = async () => {
    try {
        if (process.env.SSO_ENABLED) {
            const rawClaims: any = await verifySsoToken(event.headers.authorization.replace('Bearer ', ''));
            
            // Ensure SSO user exists in DB and get normalized claims + Hasura claims
            const { normalizedClaims, hasuraClaims } = await ensureSsoUser(rawClaims);
            
            // Add Hasura claims to the normalized claims (matches Cognito structure)
            const enrichedClaims = {
                ...normalizedClaims,
                'https://hasura.io/jwt/claims': hasuraClaims,
            };
            
            const updatedEvent = setEvent({ ...event, claims: enrichedClaims });
            logEvent(updatedEvent);
        }
        else {
            const claims = await verifier.verify(event.headers.authorization.replace('Bearer ', ''));
            const updatedEvent = setEvent({ ...event, claims });
            logEvent(updatedEvent);
        }
        return router(authRoutes);
    }
    catch (err) {
        console.log(err);
        return "Your authorization token is invalid";
    }
}

export const cognitoRouter = async () => {
    logEvent(event);
    if (event.triggerSource === "PreSignUp_SignUp") {
        return preSignUpSignUp();
    }
    else if (event.triggerSource === "PostConfirmation_ConfirmSignUp") {
        return postConfirmationConfirmSignUp();
    }
    else if (['TokenGeneration_Authentication', 'TokenGeneration_RefreshTokens', 'TokenGeneration_AuthenticateDevice'].includes(event.triggerSource)) {
        return tokenGeneration();
    }
    else {
        return event;
    }
}

export const internalRouter = async () => {
    logEvent(event);
    return router(internalRoutes);
}

export const publicRouter = async () => {
    logEvent(event);
    return router(publicRoutes);
}

export const scheduledRouter = async () => {
    logEvent(event);
    return router(scheduledRoutes);
}

export const hasuraRouter = async () => {
    logEvent(event);
    if (event.headers.webhooksecret === process.env.WEBHOOKSECRET) {
        return router(hasuraRoutes);
    }
}