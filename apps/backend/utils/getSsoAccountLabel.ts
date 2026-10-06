import { db } from './db';

// "your UIC account", or "your organization account" when no organization name is set
// in Account > System. Mirrors ssoAccountLabel in the frontend. Expects db to be connected.
export const getSsoAccountLabel = async () => {
    const name = String((await db.query({
        text: `SELECT value FROM options WHERE key='sso_organization_name'`,
    })).rows?.[0]?.value ?? '').trim();
    return `your ${name || 'organization'} account`;
};
