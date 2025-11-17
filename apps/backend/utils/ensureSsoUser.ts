import { db } from './db';

interface SsoClaims {
    oid?: string;  // Azure AD user ID
    sub?: string;  // Subject (fallback)
    email?: string;
    name?: string;
    preferred_username?: string;
    [key: string]: any;
}

export const ensureSsoUser = async (claims: SsoClaims) => {
    const userId = claims.oid || claims.sub;
    const email = claims.email || claims.preferred_username;
    const name = claims.name || 'SSO User';

    if (!userId) {
        throw new Error('SSO claims missing user ID (oid or sub)');
    }

    await db.connect();
    
    // Check if user exists
    const existingUser = await db.query({
        text: `SELECT id FROM "users" WHERE "id" = $1`,
        values: [userId],
    });

    // Create user if doesn't exist
    if (existingUser.rows.length === 0) {
        await db.query({
            text: `INSERT INTO "users" ("id", "email", "name") VALUES ($1, $2, $3)`,
            values: [userId, email, name],
        });
        console.log(`Created SSO user: ${userId} (${email})`);
    }

    await db.clean();
    
    // Normalize claims to match Cognito structure
    // This ensures existing code using event.claims.sub and event.claims.profile works
    const normalizedClaims = {
        ...claims,
        sub: userId,  // Map oid -> sub for consistency
        profile: userId,  // Use userId as profile/org-id
    };
    
    // Return normalized claims with Hasura claims
    return {
        normalizedClaims,
        hasuraClaims: {
            'x-hasura-allowed-roles': ['user'],
            'x-hasura-default-role': 'user',
            'x-hasura-user-id': userId,
            'x-hasura-org-id': userId,
        }
    };
};
