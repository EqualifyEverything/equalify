import jwt from 'jsonwebtoken';
import jwksClient from 'jwks-rsa';

// Lazy: this module is imported unconditionally (routes/index.ts), including
// when SSO_ENABLED is false and SSO_JWKS is never set on the Lambda. Only
// verifySsoToken() actually requires it, and that's only ever called from
// the SSO_ENABLED branch — so the check/client construction must happen on
// first use, not at import time, or every cold start crashes when SSO isn't
// configured at all.
let client: ReturnType<typeof jwksClient> | undefined;

const getClient = () => {
    if (!process.env.SSO_JWKS) {
        throw new Error('SSO_JWKS environment variable is required for SSO authentication');
    }
    if (!client) {
        client = jwksClient({
            jwksUri: process.env.SSO_JWKS,
            cache: true,
            cacheMaxAge: 86400000, // 24 hours
        });
    }
    return client;
}

// Get signing key
const getKey = (header: any, callback: any) => {
    getClient().getSigningKey(header.kid, (err, key) => {
        if (err) {
            callback(err);
            return;
        }
        const signingKey = key?.getPublicKey();
        callback(null, signingKey);
    });
}

// Verify token
export const verifySsoToken = (token: string) => {
    return new Promise((resolve, reject) => {
        jwt.verify(
            token,
            getKey,
            {
                audience: process.env.SSO_CLIENT_ID,
                issuer: process.env.SSO_TENANT,
                algorithms: ['RS256'],
            },
            (err, decoded) => {
                if (err) {
                    reject(err);
                } else {
                    resolve(decoded);
                }
            }
        );
    });
}
