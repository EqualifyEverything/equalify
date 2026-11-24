import { db, event, logAuditTagAssignmentChanges, validateUuid } from "#src/utils";

//
// Add audits to, or remove audits from, one tag (Tag Report > Audits).
// Unlike setAuditTags, this only touches the audits named, so it can't
// overwrite a concurrent edit to an audit's other tags. Each audit that
// actually changes gets its own log entry, as if it were edited from its page.
//
// POST /auth/updateTagAudits { tagId, add?: auditId[], remove?: auditId[] }
//

const MAX_AUDITS_PER_CHANGE = 500;

export const updateTagAudits = async () => {
    const { tagId, add = [], remove = [] } = event.body ?? {};
    const isIdList = (value: unknown) =>
        Array.isArray(value) && value.length <= MAX_AUDITS_PER_CHANGE && value.every(validateUuid);
    if (!validateUuid(tagId) || !isIdList(add) || !isIdList(remove)) {
        return { status: 'error', message: 'A valid tag and list of audits are required.' };
    }
    if (add.some((id: string) => remove.includes(id))) {
        return { status: 'error', message: "An audit can't be added and removed at the same time." };
    }

    await db.connect();
    const tag = (await db.query({
        text: `SELECT "id", "name" FROM "audit_tags" WHERE "id"=$1`,
        values: [tagId],
    })).rows?.[0];
    if (!tag) {
        await db.clean();
        return { status: 'error', message: 'This tag no longer exists. It may have been merged or deleted.' };
    }

    let added: string[] = [];
    let removed: string[] = [];
    try {
        await db.query('BEGIN');
        if (add.length) {
            // Skips quick scans and audits that don't exist; ON CONFLICT rows
            // (already tagged) aren't returned, so this is only real additions
            added = (await db.query({
                text: `INSERT INTO "audit_tag_assignments" ("audit_id", "tag_id", "created_by")
                       SELECT "id", $2, $3 FROM "audits"
                       WHERE "id" = ANY($1::uuid[]) AND "interval" <> 'Quick Scan'
                       ON CONFLICT DO NOTHING
                       RETURNING "audit_id"`,
                values: [add, tag.id, event.claims.sub],
            })).rows.map((row) => row.audit_id);
        }
        if (remove.length) {
            removed = (await db.query({
                text: `DELETE FROM "audit_tag_assignments" WHERE "tag_id"=$1 AND "audit_id" = ANY($2::uuid[]) RETURNING "audit_id"`,
                values: [tag.id, remove],
            })).rows.map((row) => row.audit_id);
        }
        for (const auditId of added) {
            await logAuditTagAssignmentChanges(auditId, [tag.id], []);
        }
        for (const auditId of removed) {
            await logAuditTagAssignmentChanges(auditId, [], [tag.id]);
        }
        await db.query('COMMIT');
    }
    catch (err) {
        await db.query('ROLLBACK');
        await db.clean();
        console.log('updateTagAudits failed:', err);
        return { status: 'error', message: `Unable to update the audits tagged "${tag.name}".` };
    }
    await db.clean();

    return { status: 'success', added, removed };
}
