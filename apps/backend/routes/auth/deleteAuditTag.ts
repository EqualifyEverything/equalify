import { db, event, isAdminUser, validateUuid } from "#src/utils";

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

    await db.query({
        text: `DELETE FROM "audit_tags" WHERE "id"=$1`,
        values: [id],
    });
    await db.clean();

    return { status: 'success' };
}
