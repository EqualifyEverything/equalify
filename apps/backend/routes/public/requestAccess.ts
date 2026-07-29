import { db, event } from "#src/utils";

//
// Public endpoint: lets someone with an SSO account but no Equalify access
// request access. Admins review requests on the Account > Requests tab.
//

export const requestAccess = async () => {
    const email = String(event.body?.email ?? '').trim().toLowerCase();
    const name = String(event.body?.name ?? '').trim();

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return { status: 'error', message: 'Please enter a valid email address.' };
    }

    if (process.env.SSO_ENABLED && process.env.SSO_EMAIL_DOMAINS) {
        const ssoEmailDomains = JSON.parse(process.env.SSO_EMAIL_DOMAINS);
        if (!ssoEmailDomains.includes(email.split('@')[1])) {
            return { status: 'error', message: `Please use your institutional email address (${ssoEmailDomains.map((domain: string) => `@${domain}`).join(', ')}).` };
        }
    }

    await db.connect();

    const userExists = (await db.query({
        text: `SELECT id FROM users WHERE lower(email)=$1`,
        values: [email],
    })).rows?.[0]?.id;
    if (userExists) {
        await db.clean();
        return { status: 'error', message: 'An account already exists for this email address — try signing in.' };
    }

    const inviteExists = (await db.query({
        text: `SELECT id FROM invites WHERE lower(email)=$1`,
        values: [email],
    })).rows?.[0]?.id;
    if (inviteExists) {
        await db.clean();
        return { status: 'success', message: 'You already have an invite — sign in with SSO to activate your account.' };
    }

    const pendingExists = (await db.query({
        text: `SELECT id FROM access_requests WHERE lower(email)=$1 AND status='pending'`,
        values: [email],
    })).rows?.[0]?.id;
    if (pendingExists) {
        await db.clean();
        return { status: 'success', message: 'Your access request is already pending review — an administrator will get to it soon.' };
    }

    await db.query({
        text: `INSERT INTO "access_requests" ("email", "name") VALUES ($1, $2)`,
        values: [email, name || null],
    });
    await db.clean();

    return { status: 'success', message: 'Request submitted! An administrator will review it shortly.' };
};
