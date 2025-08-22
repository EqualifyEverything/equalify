import { db, event } from '#src/utils'

export const getAccount = async () => {
    await db.connect();
    const row = (await db.query({
        text: `SELECT * FROM "users" WHERE "id"=$1`,
        values: [event.claims.sub],
    })).rows[0];
    await db.clean();
    return row;
}