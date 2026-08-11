import { event, db } from "#src/utils";

export const postConfirmationConfirmSignUp = async () => {
    const { sub, email, name, website } = event.request.userAttributes;

    if (website === 'api-flow') {
        return event;
    }

    await db.connect();

    // First real signup on a fresh instance becomes admin — otherwise there
    // is no way to reach the admin-gated routes (getAccessRequests,
    // reviewAccessRequest) at all. Mirrors ensureSsoUser's same bootstrap.
    const anyUserExists = (await db.query({
        text: `SELECT id FROM users LIMIT 1`
    })).rows?.[0]?.id;

    if (!anyUserExists) {
        await db.query({
            text: `INSERT INTO "users" ("id", "email", "name", "type") VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING`,
            values: [sub, email, name ?? 'User', 'admin'],
        });
    } else {
        await db.query({
            text: `INSERT INTO "users" ("id", "email", "name") VALUES ($1, $2, $3) ON CONFLICT DO NOTHING`,
            values: [sub, email, name ?? 'User'],
        });
    }

    await db.clean();
    return event;
}