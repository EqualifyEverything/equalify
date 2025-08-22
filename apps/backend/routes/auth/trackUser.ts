import { db, event, getAnalytics } from '#src/utils';

export const trackUser = async () => {
    await db.connect();
    const { sub } = event.claims;
    const analytics = getAnalytics();
    await db.query({
        text: `UPDATE "users" SET "analytics"=$1 WHERE "id"=$2`,
        values: [JSON.stringify(analytics), sub],
    });

    /*
    if (!event.claims.email.includes('+')) {
        await fetch(process.env.SLACK_WEBHOOK, {
            method: 'POST',
            body: JSON.stringify({
                text: `${isStaging ? '[STAGING] ' : ''}*${event.claims.email}* just signed up from *${analytics?.city}, ${analytics?.state}* on *${analytics?.device}*`
            })
        })
    }
    */

    await db.clean();
    return;
}