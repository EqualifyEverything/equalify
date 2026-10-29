import { db, event, isAdminUser, logAuditTagEvent, validateUuid } from "#src/utils";

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
            text: `SELECT "id", "name" FROM "audit_tags" WHERE "id"=$1`,
            values: [targetId],
        })).rows?.[0];
        const source = (await db.query({
            text: `SELECT "id", "name", (SELECT count(*)::int FROM "audit_tag_assignments" WHERE "tag_id"=$1) AS "audit_count"
                   FROM "audit_tags" WHERE "id"=$1`,
            values: [sourceId],
        })).rows?.[0];
        if (!target || !source) {
            throw new Error('Tag not found');
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
        // Inside the transaction, so a merge is never logged unless it happened
        await logAuditTagEvent(
            `Tag "${source.name}" merged into "${target.name}" (${source.audit_count} audit${source.audit_count === 1 ? '' : 's'})`,
            { sourceTagId: source.id, sourceTagName: source.name, targetTagId: target.id, targetTagName: target.name, auditCount: source.audit_count },
        );
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
