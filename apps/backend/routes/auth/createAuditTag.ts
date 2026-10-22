import { db, event, normalizeAuditTagName, validateAuditTagName } from "#src/utils";

//
// Create a shared audit tag. If a tag with the same name (case-insensitive)
// already exists, that tag is returned instead, so two people creating the
// same tag at once can't produce duplicates.
//

export const createAuditTag = async () => {
    const name = normalizeAuditTagName(event.body?.name);
    const invalid = validateAuditTagName(name);
    if (invalid) {
        return { status: 'error', message: invalid };
    }

    await db.connect();
    let tag = (await db.query({
        text: `INSERT INTO "audit_tags" ("name", "created_by") VALUES ($1, $2)
               ON CONFLICT ((lower("name"))) DO NOTHING
               RETURNING "id", "name"`,
        values: [name, event.claims.sub],
    })).rows?.[0];
    const created = !!tag;
    if (!tag) {
        tag = (await db.query({
            text: `SELECT "id", "name" FROM "audit_tags" WHERE lower("name")=lower($1)`,
            values: [name],
        })).rows?.[0];
    }
    await db.clean();

    return { status: 'success', created, tag };
}
