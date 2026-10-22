import { db } from "./db";

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

// Expects db to already be connected
export const isAdminUser = async (userId: string) =>
    (await db.query({
        text: `SELECT type FROM users WHERE id=$1`,
        values: [userId],
    })).rows?.[0]?.type === 'admin';
