import { db, event, validateUuid } from "#src/utils";

//
// Replace an audit's full set of tags. The frontend always sends the complete
// list, so adding and removing a tag both go through this one route.
//

export const setAuditTags = async () => {
    const { auditId, tagIds } = event.body ?? {};
    if (!validateUuid(auditId) || !Array.isArray(tagIds) || !tagIds.every(validateUuid)) {
        return { status: 'error', message: 'A valid audit and list of tags are required.' };
    }

    await db.connect();
    try {
        await db.query('BEGIN');
        await db.query({
            text: `DELETE FROM "audit_tag_assignments" WHERE "audit_id"=$1 AND NOT ("tag_id" = ANY($2::uuid[]))`,
            values: [auditId, tagIds],
        });
        if (tagIds.length) {
            await db.query({
                text: `INSERT INTO "audit_tag_assignments" ("audit_id", "tag_id", "created_by")
                       SELECT $1, "id", $3 FROM "audit_tags" WHERE "id" = ANY($2::uuid[])
                       ON CONFLICT DO NOTHING`,
                values: [auditId, tagIds, event.claims.sub],
            });
        }
        await db.query('COMMIT');
    }
    catch (err) {
        await db.query('ROLLBACK');
        await db.clean();
        console.log('setAuditTags failed:', err);
        return { status: 'error', message: 'Unable to update tags for this audit.' };
    }
    await db.clean();

    return { status: 'success' };
}
