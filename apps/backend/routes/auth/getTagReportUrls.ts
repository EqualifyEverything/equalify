import { db, event, getTagReportScope, validateUuid } from "#src/utils";

//
// Tag Report: URLs with the most blockers across the tag's audits (each
// audit's latest scan). A URL that's in two audits is listed once per audit,
// since each audit tracks and scans it separately.
//
// GET /auth/getTagReportUrls?id=<tagId>&page=<n>&pageSize=<n>
//

export const getTagReportUrls = async () => {
    const params = (event.queryStringParameters ?? {}) as any;
    const tagId = params.id;
    if (!validateUuid(tagId)) {
        return { statusCode: 400, body: JSON.stringify({ error: "Invalid tag" }) };
    }
    let page = Math.max(0, parseInt(params.page ?? "0", 10) || 0);
    const pageSize = Math.min(100, Math.max(1, parseInt(params.pageSize ?? "5", 10) || 5));

    await db.connect();
    const scope = await getTagReportScope(tagId);
    if (!scope) {
        await db.clean();
        return { statusCode: 404, body: JSON.stringify({ error: "Tag not found" }) };
    }
    const listed = `FROM "blockers" "b"
                    JOIN "urls" "u" ON "u"."id"="b"."url_id"
                    JOIN "audits" "a" ON "a"."id"="b"."audit_id"
                    WHERE "b"."scan_id" = ANY($1::uuid[])
                    GROUP BY "u"."url", "a"."id", "a"."name"`;
    // The audit id is the last tie-break: the same URL in two audits with the
    // same count would otherwise have no fixed order, and could repeat on one
    // page and be missing from the next
    const fetchPage = async (pageIndex: number) => (await db.query({
        text: `SELECT "u"."url"::text AS "key", "a"."id" AS "audit_id", "a"."name" AS "audit_name",
                      COUNT(*)::int AS "count", COUNT(*) OVER ()::int AS "total_count"
               ${listed}
               ORDER BY 4 DESC, 1, "a"."id"
               LIMIT $2 OFFSET $3`,
        values: [scope.latestScanIds, pageSize, pageIndex * pageSize],
    })).rows;
    let rows = await fetchPage(page);
    if (rows.length === 0 && page > 0) {
        // Past the end: the list shrank since this page was asked for (an
        // audit left the tag, or was rescanned). An empty page has no row to
        // carry the total, so count it directly and answer with the last page
        // there is; `page` in the response says which one that was.
        const total: number = (await db.query({
            text: `SELECT COUNT(*)::int AS "count" FROM (SELECT 1 ${listed}) "rows"`,
            values: [scope.latestScanIds],
        })).rows[0].count;
        page = Math.max(0, Math.ceil(total / pageSize) - 1);
        rows = await fetchPage(page);
    }
    await db.clean();

    return {
        statusCode: 200,
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            items: rows.map((row) => ({ key: row.key, count: row.count, auditId: row.audit_id, auditName: row.audit_name })),
            totalCount: rows[0]?.total_count ?? 0,
            page,
            pageSize,
        }),
    };
}
