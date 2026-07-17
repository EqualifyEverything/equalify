import { db, event } from "#src/utils";

export const getAccessRequests = async () => {
    await db.connect();
    const requesterType = (await db.query({
        text: `SELECT type FROM users WHERE id=$1`,
        values: [event.claims.sub],
    })).rows?.[0]?.type;
    if (requesterType !== 'admin') {
        await db.clean();
        return { status: 'error', message: 'Admin access required.' };
    }

    const requests = (await db.query({
        text: `SELECT "id", "name", "email", "status", "created_at" FROM "access_requests" WHERE "status"='pending' ORDER BY "created_at" ASC`,
    })).rows;
    await db.clean();

    return { status: 'success', requests };
};
