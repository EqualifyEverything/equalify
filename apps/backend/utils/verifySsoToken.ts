import jwt from 'jsonwebtoken';
import jwksClient from 'jwks-rsa';

if (!process.env.SSO_JWKS) {
    throw new Error('SSO_JWKS environment variable is required for SSO authentication');
}

const client = jwksClient({
    jwksUri: process.env.SSO_JWKS,
    cache: true,
    cacheMaxAge: 86400000, // 24 hours
});

// Get signing key
const getKey = (header: any, callback: any) => {
    client.getSigningKey(header.kid, (err, key) => {
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
