import { db, event, getAnalytics } from '#src/utils';

export const trackUser = async () => {
    await db.connect();
    const { sub } = event.claims;
    const analytics = getAnalytics();
    await db.query({
        text: `UPDATE "users" SET "analytics"=$1 WHERE "id"=$2`,
        values: [JSON.stringify(analytics), sub],
    });

    await db.clean();
    return;
}