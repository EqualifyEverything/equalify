import { db, event, isAdminUser, logAuditTagEvent, normalizeAuditTagName, validateAuditTagName, validateUuid } from "#src/utils";

//
// Admin-only: rename an audit tag (Account > Tags).
//

export const renameAuditTag = async () => {
    const { id } = event.body ?? {};
    const name = normalizeAuditTagName(event.body?.name);
    const invalid = !validateUuid(id) ? 'Invalid tag.' : validateAuditTagName(name);
    if (invalid) {
        return { status: 'error', message: invalid };
    }

    await db.connect();
    if (!(await isAdminUser(event.claims.sub))) {
        await db.clean();
        return { status: 'error', message: 'Admin access required.' };
    }

    try {
        // The FROM subquery reads the row as it was before the update
        const updated = (await db.query({
            text: `UPDATE "audit_tags" SET "name"=$1
                   FROM (SELECT "id", "name" AS "old_name" FROM "audit_tags" WHERE "id"=$2) AS "previous"
                   WHERE "audit_tags"."id"="previous"."id"
                   RETURNING "audit_tags"."id", "audit_tags"."name", "previous"."old_name"`,
            values: [name, id],
        })).rows?.[0];
        if (!updated) {
            await db.clean();
            return { status: 'error', message: 'Tag not found.' };
        }
        if (updated.old_name !== updated.name) {
            await logAuditTagEvent(
                `Tag "${updated.old_name}" renamed to "${updated.name}"`,
                { tagId: updated.id, oldName: updated.old_name, newName: updated.name },
            );
        }
        await db.clean();
        return { status: 'success', tag: { id: updated.id, name: updated.name } };
    }
    catch (err) {
        await db.clean();
        if (err?.code === '23505') {
            return { status: 'error', message: `A tag named "${name}" already exists. Use Merge to combine them.` };
        }
        console.log('renameAuditTag failed:', err);
        return { status: 'error', message: 'Unable to rename tag.' };
    }
}
