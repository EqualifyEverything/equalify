import { db, event, getTagReportScope, validateUuid } from "#src/utils";

//
// Tag Report: most common blockers (grouped by message, like
// getMostCommonBlockers) across the tag's audits' latest scans, with how many
// of the audits each one appears in.
//
// GET /auth/getTagReportBlockers?id=<tagId>&page=<n>&pageSize=<n>
//

export const getTagReportBlockers = async () => {
    const params = (event.queryStringParameters ?? {}) as any;
    const tagId = params.id;
    if (!validateUuid(tagId)) {
        return { statusCode: 400, body: JSON.stringify({ error: "Invalid tag" }) };
    }
    const page = Math.max(0, parseInt(params.page ?? "0", 10) || 0);
    const pageSize = Math.min(100, Math.max(1, parseInt(params.pageSize ?? "5", 10) || 5));

    await db.connect();
    const scope = await getTagReportScope(tagId);
    if (!scope) {
        await db.clean();
        return { statusCode: 404, body: JSON.stringify({ error: "Tag not found" }) };
    }
    const rows = (await db.query({
        text: `SELECT "m"."content"::text AS "key",
                      COUNT(DISTINCT "b"."id")::int AS "count",
                      COUNT(DISTINCT "b"."audit_id")::int AS "audit_count",
                      MIN("m"."category") AS "category",
                      COUNT(*) OVER ()::int AS "total_count"
               FROM "blockers" "b"
               JOIN "blocker_messages" "bm" ON "b"."id"="bm"."blocker_id"
               JOIN "messages" "m" ON "bm"."message_id"="m"."id"
               WHERE "b"."scan_id" = ANY($1::uuid[])
               GROUP BY "m"."content"
               ORDER BY 2 DESC, 1
               LIMIT $2 OFFSET $3`,
        values: [scope.latestScanIds, pageSize, page * pageSize],
    })).rows;
    await db.clean();

    return {
        statusCode: 200,
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            items: rows.map((row) => ({ key: row.key, count: row.count, auditCount: row.audit_count, category: row.category })),
            totalCount: rows[0]?.total_count ?? 0,
            page,
            pageSize,
        }),
    };
}
