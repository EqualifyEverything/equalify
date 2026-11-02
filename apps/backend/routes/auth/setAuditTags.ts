import { db, event, logAuditTagAssignmentChanges, validateUuid } from "#src/utils";

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
        const removedIds: string[] = (await db.query({
            text: `DELETE FROM "audit_tag_assignments" WHERE "audit_id"=$1 AND NOT ("tag_id" = ANY($2::uuid[])) RETURNING "tag_id"`,
            values: [auditId, tagIds],
        })).rows.map((row) => row.tag_id);
        // ON CONFLICT rows aren't returned, so this is only the newly added tags
        const addedIds: string[] = tagIds.length ? (await db.query({
            text: `INSERT INTO "audit_tag_assignments" ("audit_id", "tag_id", "created_by")
                   SELECT $1, "id", $3 FROM "audit_tags" WHERE "id" = ANY($2::uuid[])
                   ON CONFLICT DO NOTHING
                   RETURNING "tag_id"`,
            values: [auditId, tagIds, event.claims.sub],
        })).rows.map((row) => row.tag_id) : [];
        await logAuditTagAssignmentChanges(auditId, addedIds, removedIds);
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
