import { db, event } from '#src/utils'

export const accountQuota = async () => {
    await db.connect();
    const quota = parseFloat((await db.query({
        text: `SELECT "quota" FROM "organizations" WHERE "id"=$1`,
        values: [event.claims.profile],
    })).rows?.[0]?.quota);
    await db.clean();
    return { quota };
}