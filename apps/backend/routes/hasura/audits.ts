import { db, event, sleep } from "#src/utils";

export const audits = async () => {
    const { op, data: { new: { id, created_at, user_id, organization_id } } } = event?.body?.event;

    if (['INSERT'].includes(op)) {
        await db.connect();
        await sleep(10000);
        const audit = (await db.query(`SELECT "name" FROM "audits" WHERE "id"=$1`, [id])).rows[0];
        const message = `${audit.name} added`
        await db.query({
            text: `INSERT INTO "logs" ("user_id","organization_id","audit_id","message") VALUES ($1, $2, $3)`,
            values: [user_id, organization_id, id, message],
        });
        await db.clean();
    }

    return;
}