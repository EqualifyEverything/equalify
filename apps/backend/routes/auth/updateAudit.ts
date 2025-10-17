import { db, event } from "#src/utils"

export const updateAudit = async () => {
    const { id, name } = event.body;
    await db.connect();
    await db.query({
        text: `UPDATE "audits" SET "name"=$1 WHERE "id"=$2`,
        values: [name, id],
    });
    await db.clean();
    return true;
}