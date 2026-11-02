import { db } from "./db";
import { event } from "./event";

export const MAX_AUDIT_TAG_LENGTH = 50;

// Trim and collapse internal whitespace so "  Main   Campus " and "Main Campus"
// are the same tag (uniqueness is also case-insensitive at the DB level)
export const normalizeAuditTagName = (name: unknown) =>
    typeof name === 'string' ? name.trim().replace(/\s+/g, ' ') : '';

export const validateAuditTagName = (name: string) => {
    if (!name) return 'Tag name is required.';
    if (name.length > MAX_AUDIT_TAG_LENGTH) return `Tag names must be ${MAX_AUDIT_TAG_LENGTH} characters or fewer.`;
    return null;
}

// Records a tag change on the Logs screen. Pass auditId for changes to one
// audit's tags; tag-wide changes (create/rename/merge/delete) leave it empty.
// Expects db to already be connected.
export const logAuditTagEvent = async (message: string, data: object, auditId: string | null = null) =>
    db.query({
        text: `INSERT INTO "logs" ("user_id", "audit_id", "message", "data") VALUES ($1, $2, $3, $4)`,
        values: [(event as any).claims.sub, auditId, message, JSON.stringify(data)],
    });

// Logs which tags were added to / removed from an audit, e.g.
// `Added tags "Library", "Sports"; removed tag "Athletics"`. Does nothing if
// nothing changed. Expects db to already be connected.
export const logAuditTagAssignmentChanges = async (auditId: string, addedIds: string[], removedIds: string[]) => {
    if (!addedIds.length && !removedIds.length) return;
    const tags: { id: string; name: string }[] = (await db.query({
        text: `SELECT "id", "name" FROM "audit_tags" WHERE "id" = ANY($1::uuid[]) ORDER BY lower("name")`,
        values: [[...addedIds, ...removedIds]],
    })).rows;
    const added = tags.filter((tag) => addedIds.includes(tag.id));
    const removed = tags.filter((tag) => removedIds.includes(tag.id));
    const describe = (verb: string, list: typeof tags) =>
        `${verb} tag${list.length === 1 ? '' : 's'} ${list.map((tag) => `"${tag.name}"`).join(', ')}`;
    const parts = [
        added.length ? describe('Added', added) : null,
        removed.length ? describe('removed', removed) : null,
    ].filter(Boolean).join('; ');
    await logAuditTagEvent(parts.charAt(0).toUpperCase() + parts.slice(1), { added, removed }, auditId);
}

// Expects db to already be connected
export const isAdminUser = async (userId: string) =>
    (await db.query({
        text: `SELECT type FROM users WHERE id=$1`,
        values: [userId],
    })).rows?.[0]?.type === 'admin';
