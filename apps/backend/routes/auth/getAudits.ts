import { db, event, validateUuid } from "#src/utils";

//
// The Audits screen's list, one page at a time. Filtering, sorting and
// counting happen in the database, so the browser only ever holds the page
// it is showing.
//
// GET /auth/getAudits?page=<n>&pageSize=<n>&search=<text>&createdBy=<user|all>
//                    &tagIds=<id,id,...>&sortBy=<column>&sortOrder=<asc|desc>
//
// - Every signed-in user can list every audit (the same reach the Hasura
//   `user` role has). Quick scans are never listed.
// - `search` matches anywhere in the audit name, ignoring case, and only
//   applies from three characters.
// - `createdBy=user` keeps audits whose creator has the caller's email.
// - `tagIds` keeps audits carrying any of the tags.
// - A `page` past the end returns the last page instead of nothing, and
//   `pagination.page` says which page that was.
//
// Each audit comes back in the shape the screen used to read from Hasura
// (`scans` holds the latest scan only), so the cards and the table render
// it unchanged.
//

// A value from the audit's latest scan, whatever that scan's status: the
// scan the Audits screen describes. NULL when the audit has never been scanned.
const fromLatestScan = (expression: string) =>
    `(SELECT ${expression} FROM "scans" "s" WHERE "s"."audit_id" = "a"."id" ORDER BY "s"."created_at" DESC LIMIT 1)`;

// Sort keys by column. These are the only pieces of a request that reach the
// SQL as text, and they come from this list, never from the request itself.
const SORT_KEYS: Record<string, string> = {
    created_at: `"a"."created_at"`,
    // Case-insensitive with numbers in numeric order ("EQT9" before "EQT14"),
    // as the table sorted names when it sorted in the browser: every run of
    // digits is left-padded to the same width before comparing.
    name: `(SELECT string_agg(CASE WHEN "part"[1] ~ '^[0-9]' THEN lpad("part"[1], 20, '0') ELSE "part"[1] END, '' ORDER BY "position")
            FROM regexp_matches(LOWER("a"."name"), '([0-9]+|[^0-9]+)', 'g') WITH ORDINALITY AS "parts"("part", "position"))`,
    user: `LOWER("u"."name")`,
    interval: `"a"."interval"`,
    tags: `(SELECT string_agg(LOWER("t"."name"), ', ' ORDER BY LOWER("t"."name"))
            FROM "audit_tag_assignments" "x" JOIN "audit_tags" "t" ON "t"."id" = "x"."tag_id"
            WHERE "x"."audit_id" = "a"."id")`,
    lastScan: fromLatestScan(`"s"."updated_at"`),
    // Audits that are scanning right now sort apart from the rest
    status: `COALESCE(${fromLatestScan(`("s"."status" = 'processing')::int`)}, 0)`,
    urls: `(SELECT COUNT(*) FROM "urls" "ur" WHERE "ur"."audit_id" = "a"."id")`,
    // Counted live, like the number shown. This is the one sort that has to
    // count blockers for every matching audit rather than just the page.
    blockers: fromLatestScan(`(SELECT COUNT(*) FROM "blockers" "b" WHERE "b"."scan_id" = "s"."id")`),
};

const DEFAULT_PAGE_SIZE = 15;
const MAX_PAGE_SIZE = 100;
const MIN_SEARCH_LENGTH = 3;

const json = (statusCode: number, body: unknown) => ({
    statusCode,
    headers: { "content-type": "application/json" },
    body: JSON.stringify(body),
});

export const getAudits = async () => {
    const params = (event.queryStringParameters ?? {}) as Record<string, string | undefined>;
    const requestedPage = Math.max(0, parseInt(params.page ?? "0", 10) || 0);
    const pageSize = Math.min(MAX_PAGE_SIZE, Math.max(1, parseInt(params.pageSize ?? "", 10) || DEFAULT_PAGE_SIZE));
    const search = (params.search ?? "").length >= MIN_SEARCH_LENGTH ? params.search! : "";
    const tagIds = (params.tagIds ?? "").split(",").filter(Boolean);
    if (!tagIds.every((id) => validateUuid(id))) {
        return json(400, { status: "error", message: "Invalid tag" });
    }
    // An unknown column falls back to the default order rather than failing
    // the screen. hasOwn, because a plain lookup would also "find" names every
    // object inherits, such as "constructor".
    const sortKey = Object.hasOwn(SORT_KEYS, params.sortBy ?? "") ? SORT_KEYS[params.sortBy!] : undefined;
    const direction = params.sortOrder === "desc" ? "DESC" : "ASC";
    // Newest first by default and as the tie-break, with the id last so pages
    // never overlap or skip when two audits compare equal
    const tieBreak = `"a"."created_at" DESC, "a"."id"`;
    const order = sortKey ? `${sortKey} ${direction} NULLS LAST, ${tieBreak}` : tieBreak;

    const where = [`"a"."interval" <> 'Quick Scan'`];
    const values: unknown[] = [];
    if (params.createdBy === "user") {
        values.push((event as any).claims?.sub);
        where.push(`"u"."email" = (SELECT "email" FROM "users" WHERE "id" = $${values.length})`);
    }
    if (search) {
        // % and _ are wildcards to ILIKE, so a search for "50%" must escape them
        values.push(`%${search.replace(/[\\%_]/g, "\\$&")}%`);
        where.push(`"a"."name" ILIKE $${values.length}`);
    }
    if (tagIds.length > 0) {
        values.push(tagIds);
        where.push(`EXISTS (SELECT 1 FROM "audit_tag_assignments" "x"
                            WHERE "x"."audit_id" = "a"."id" AND "x"."tag_id" = ANY($${values.length}::uuid[]))`);
    }
    const matching = `FROM "audits" "a"
                      LEFT JOIN "users" "u" ON "u"."id" = "a"."user_id"
                      WHERE ${where.join(" AND ")}`;

    try {
        await db.connect();

        // Counted on its own, before the page, so the total is still known
        // (and the page can be pulled back in range) when the requested page
        // is past the end
        const totalCount: number = (await db.query({
            text: `SELECT COUNT(*)::int AS "count" ${matching}`,
            values,
        })).rows[0].count;
        const totalPages = Math.max(1, Math.ceil(totalCount / pageSize));
        const page = Math.min(requestedPage, totalPages - 1);

        // "page" picks and orders just the ids; the per-audit lookups below
        // then run only for the audits on this page
        const rows = (await db.query({
            text: `WITH "page" AS (
                       SELECT "a"."id", ROW_NUMBER() OVER (ORDER BY ${order}) AS "position"
                       ${matching}
                       ORDER BY ${order}
                       LIMIT $${values.length + 1} OFFSET $${values.length + 2}
                   )
                   SELECT "a"."id", "a"."created_at", "a"."name", "a"."interval",
                          "u"."name" AS "user_name", "u"."email" AS "user_email",
                          "ls"."id" AS "scan_id", "ls"."status" AS "scan_status",
                          "ls"."percentage"::float8 AS "scan_percentage", "ls"."updated_at" AS "scan_updated_at",
                          (SELECT COUNT(*)::int FROM "blockers" "b" WHERE "b"."scan_id" = "ls"."id") AS "blocker_count",
                          (SELECT COUNT(*)::int FROM "urls" "ur" WHERE "ur"."audit_id" = "a"."id") AS "url_count",
                          COALESCE((SELECT json_agg(json_build_object('id', "t"."id", 'name', "t"."name") ORDER BY "t"."name")
                                    FROM "audit_tag_assignments" "x" JOIN "audit_tags" "t" ON "t"."id" = "x"."tag_id"
                                    WHERE "x"."audit_id" = "a"."id"), '[]'::json) AS "tags"
                   FROM "page" "p"
                   JOIN "audits" "a" ON "a"."id" = "p"."id"
                   LEFT JOIN "users" "u" ON "u"."id" = "a"."user_id"
                   LEFT JOIN LATERAL (
                       SELECT "s"."id", "s"."status", "s"."percentage", "s"."updated_at"
                       FROM "scans" "s" WHERE "s"."audit_id" = "a"."id"
                       ORDER BY "s"."created_at" DESC LIMIT 1
                   ) "ls" ON true
                   ORDER BY "p"."position"`,
            values: [...values, pageSize, page * pageSize],
        })).rows;

        return json(200, {
            status: "success",
            audits: rows.map((row) => ({
                id: row.id,
                created_at: row.created_at,
                name: row.name,
                interval: row.interval,
                user: { name: row.user_name ?? "", email: row.user_email ?? "" },
                scans: row.scan_id
                    ? [{
                        blockers_aggregate: { aggregate: { count: row.blocker_count } },
                        status: row.scan_status,
                        percentage: row.scan_percentage,
                        updated_at: row.scan_updated_at,
                    }]
                    : [],
                urls_aggregate: { aggregate: { count: row.url_count } },
                tag_assignments: row.tags.map((tag: { id: string; name: string }) => ({ tag })),
            })),
            pagination: { page, pageSize, totalCount, totalPages },
        });
    }
    catch (err) {
        // The detail goes to CloudWatch; the caller only needs to know it failed
        console.error("getAudits error:", err);
        return json(500, { status: "error", message: "Unable to load audits" });
    }
    finally {
        await db.clean().catch(() => { });
    }
}
