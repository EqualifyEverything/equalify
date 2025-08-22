import { db, event } from '#src/utils';

export const getAuditDetails = async () => {
    await db.connect();
    const row = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id"=$1 AND "organization_id"=$2`,
        values: [event.queryStringParameters.id, event.claims.profile],
    })).rows?.[0];
    await db.clean();
    return row;
}