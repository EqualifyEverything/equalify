import { db, event, isAdminUser, validateUuid } from "#src/utils";

//
// Admin-only: merge one audit tag into another (Account > Tags). Every audit
// tagged with the source tag gets the target tag, then the source tag is
// deleted (its assignments cascade).
//

export const mergeAuditTags = async () => {
    const { sourceId, targetId } = event.body ?? {};
    if (!validateUuid(sourceId) || !validateUuid(targetId) || sourceId === targetId) {
        return { status: 'error', message: 'Choose two different tags to merge.' };
    }

    await db.connect();
    if (!(await isAdminUser(event.claims.sub))) {
        await db.clean();
        return { status: 'error', message: 'Admin access required.' };
    }

    try {
        await db.query('BEGIN');
        const target = (await db.query({
            text: `SELECT "id" FROM "audit_tags" WHERE "id"=$1`,
            values: [targetId],
        })).rows?.[0];
        if (!target) {
            throw new Error('Target tag not found');
        }
        await db.query({
            text: `INSERT INTO "audit_tag_assignments" ("audit_id", "tag_id", "created_by")
                   SELECT "audit_id", $2, $3 FROM "audit_tag_assignments" WHERE "tag_id"=$1
                   ON CONFLICT DO NOTHING`,
            values: [sourceId, targetId, event.claims.sub],
        });
        await db.query({
            text: `DELETE FROM "audit_tags" WHERE "id"=$1`,
            values: [sourceId],
        });
        await db.query('COMMIT');
    }
    catch (err) {
        await db.query('ROLLBACK');
        await db.clean();
        console.log('mergeAuditTags failed:', err);
        return { status: 'error', message: 'Unable to merge tags.' };
    }
    await db.clean();

    return { status: 'success' };
}
