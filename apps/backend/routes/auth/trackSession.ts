import { db, event, getAnalytics } from '#src/utils';

// One row per authenticated app load or login. The frontend calls this
// (utils/trackSession.ts) right after the backend has accepted the user's
// token, so every row is a real, authorized session. Feeds the monthly KPIs
// on Account > Statistics (getSystemStats.ts): sessions started, active
// users, and units served.
//
// The SSO login flow also posts the user's org fields from Microsoft Graph
// (/me: department, companyName, officeLocation, jobTitle). Those land on
// the session row and are merged into users.analytics so "units served"
// can be counted per month without a department claim in the ID token.

const ORG_FIELDS = ['department', 'companyName', 'officeLocation', 'jobTitle'] as const;

const cleanString = (value: unknown): string | undefined => {
    if (typeof value !== 'string') return undefined;
    const trimmed = value.trim().slice(0, 200);
    return trimmed.length ? trimmed : undefined;
};

export const trackSession = async () => {
    const { sub } = event.claims;
    // SSO users never carry cognito:username (same check as updateUser.ts)
    const authMethod = event.claims['cognito:username'] ? 'cognito' : 'sso';

    const body = (event.body && typeof event.body === 'object') ? event.body : {};
    const org: Record<string, string> = {};
    for (const field of ORG_FIELDS) {
        const value = cleanString(body[field]);
        if (value) org[field] = value;
    }

    await db.connect();
    await db.query({
        text: `INSERT INTO "sessions" ("user_id", "auth_method", "department", "analytics") VALUES ($1, $2, $3, $4)`,
        values: [sub, authMethod, org.department ?? null, JSON.stringify({ ...getAnalytics(), ...org })],
    });
    if (Object.keys(org).length) {
        await db.query({
            text: `UPDATE "users" SET "analytics" = COALESCE("analytics", '{}'::jsonb) || $1::jsonb WHERE "id" = $2`,
            values: [JSON.stringify(org), sub],
        });
    }
    await db.clean();

    return { success: true };
}
