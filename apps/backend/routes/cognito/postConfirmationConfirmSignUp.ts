import { event, db } from "#src/utils";

export const postConfirmationConfirmSignUp = async () => {
    const { sub, email, name, website } = event.request.userAttributes;

    if (website === 'api-flow') {
        return event;
    }

    await db.connect();
    await db.query({
        text: `INSERT INTO "users" ("id", "email", "name") VALUES ($1, $2, $3) ON CONFLICT DO NOTHING`,
        values: [sub, email, name ?? 'User'],
    });

    await db.clean();
    return event;
}