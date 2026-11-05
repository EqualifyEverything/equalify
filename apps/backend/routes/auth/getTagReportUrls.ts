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
    const page = Math.max(0, parseInt(params.page ?? "0", 10) || 0);
    const pageSize = Math.min(100, Math.max(1, parseInt(params.pageSize ?? "5", 10) || 5));

    await db.connect();
    const scope = await getTagReportScope(tagId);
    if (!scope) {
        await db.clean();
        return { statusCode: 404, body: JSON.stringify({ error: "Tag not found" }) };
    }
    const rows = (await db.query({
        text: `SELECT "u"."url"::text AS "key", "a"."id" AS "audit_id", "a"."name" AS "audit_name",
                      COUNT(*)::int AS "count", COUNT(*) OVER ()::int AS "total_count"
               FROM "blockers" "b"
               JOIN "urls" "u" ON "u"."id"="b"."url_id"
               JOIN "audits" "a" ON "a"."id"="b"."audit_id"
               WHERE "b"."scan_id" = ANY($1::uuid[])
               GROUP BY "u"."url", "a"."id", "a"."name"
               ORDER BY 4 DESC, 1
               LIMIT $2 OFFSET $3`,
        values: [scope.latestScanIds, pageSize, page * pageSize],
    })).rows;
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
