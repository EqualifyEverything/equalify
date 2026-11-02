import { db, event, isAdminUser, logAuditTagEvent, validateUuid } from "#src/utils";

//
// Admin-only: delete an audit tag (Account > Tags). Removing it from audits
// happens via the audit_tag_assignments ON DELETE CASCADE.
//

export const deleteAuditTag = async () => {
    const { id } = event.body ?? {};
    if (!validateUuid(id)) {
        return { status: 'error', message: 'Invalid tag.' };
    }

    await db.connect();
    if (!(await isAdminUser(event.claims.sub))) {
        await db.clean();
        return { status: 'error', message: 'Admin access required.' };
    }

    // Every part of a statement sees the same snapshot, so the count is taken
    // before the delete (and its cascade) removes the assignments
    const deleted = (await db.query({
        text: `WITH "deleted" AS (DELETE FROM "audit_tags" WHERE "id"=$1 RETURNING "id", "name")
               SELECT "name", (SELECT count(*)::int FROM "audit_tag_assignments" WHERE "tag_id"=$1) AS "audit_count"
               FROM "deleted"`,
        values: [id],
    })).rows?.[0];
    if (deleted) {
        await logAuditTagEvent(
            `Tag "${deleted.name}" deleted (removed from ${deleted.audit_count} audit${deleted.audit_count === 1 ? '' : 's'})`,
            { tagId: id, tagName: deleted.name, auditCount: deleted.audit_count },
        );
    }
    await db.clean();

    return { status: 'success' };
}
