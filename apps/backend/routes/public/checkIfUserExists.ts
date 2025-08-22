import { db, event } from '#src/utils'

export const checkIfUserExists = async () => {
    await db.connect();
    const userExists = (await db.query({
        text: `SELECT "id" FROM "users" WHERE "email"=$1`,
        values: [event.body.email],
    })).rows?.[0]?.id;
    await db.clean();

    return { userExists };
}