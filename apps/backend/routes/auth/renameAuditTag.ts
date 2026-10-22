import { db, event, isAdminUser, normalizeAuditTagName, validateAuditTagName, validateUuid } from "#src/utils";

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
        const updated = (await db.query({
            text: `UPDATE "audit_tags" SET "name"=$1 WHERE "id"=$2 RETURNING "id", "name"`,
            values: [name, id],
        })).rows?.[0];
        await db.clean();
        if (!updated) {
            return { status: 'error', message: 'Tag not found.' };
        }
        return { status: 'success', tag: updated };
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
