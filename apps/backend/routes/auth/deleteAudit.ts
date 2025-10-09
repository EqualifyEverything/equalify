import { db, event } from '#src/utils';

export const deleteAudit = async () => {
    const { id } = event.body;
    await db.connect();
    await db.query({
        text: `DELETE FROM "audits" WHERE "id"=$1`,
        values: [id],
    })
    await db.clean();

    return {
        status: 'success',
        message: 'Audit successfully deleted',
    };
}
