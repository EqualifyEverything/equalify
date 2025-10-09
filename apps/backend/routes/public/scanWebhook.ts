import { db, event } from "#src/utils"

export const scanWebhook = async () => {
    console.log(JSON.stringify(event));
    await db.connect();
    await db.query({
        text: `UPDATE "audits" SET "status"=$1, "response"=$2 WHERE "id"=$3`,
        values: ['complete', JSON.stringify(event.body), event.body.auditId],
    })
    await db.clean();
    return;
} 