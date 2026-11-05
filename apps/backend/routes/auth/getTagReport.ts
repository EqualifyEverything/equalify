import { db, event, getTagReportScope, parseTagReportDays, validateUuid } from "#src/utils";

//
// Tag Report: the tag's audits plus combined stats across each audit's latest
// scan. See utils/tagReport.ts for which scans these numbers come from.
//
// GET /auth/getTagReport?id=<tagId>&days=<chart range>
//

export const getTagReport = async () => {
    const params = (event.queryStringParameters ?? {}) as any;
    const tagId = params.id;
    if (!validateUuid(tagId)) {
        return { statusCode: 400, body: JSON.stringify({ error: "Invalid tag" }) };
    }
    const days = parseTagReportDays(params.days);

    await db.connect();
    const scope = await getTagReportScope(tagId, days);
    if (!scope) {
        await db.clean();
        return { statusCode: 404, body: JSON.stringify({ error: "Tag not found" }) };
    }
    const { tag, audits, latestScanIds } = scope;

    const breakdown = (await db.query({
        text: `SELECT COUNT(*)::int AS "total",
                      COUNT(DISTINCT "b"."content_hash_id")::int AS "unique_count",
                      COUNT(DISTINCT "b"."url_id")::int AS "urls_with_blockers",
                      COUNT(*) FILTER (WHERE COALESCE("u"."type", 'html') = 'pdf')::int AS "pdf",
                      COUNT(*) FILTER (WHERE COALESCE("u"."type", 'html') <> 'pdf')::int AS "html"
               FROM "blockers" "b"
               LEFT JOIN "urls" "u" ON "u"."id"="b"."url_id"
               WHERE "b"."scan_id" = ANY($1::uuid[])`,
        values: [latestScanIds],
    })).rows[0];

    // Unique blockers (same normalized HTML) found in more than one audit,
    // i.e. fixes that would pay off across several audits at once
    const shared = (await db.query({
        text: `SELECT COUNT(*)::int AS "count" FROM (
                   SELECT "content_hash_id" FROM "blockers"
                   WHERE "scan_id" = ANY($1::uuid[])
                   GROUP BY "content_hash_id"
                   HAVING COUNT(DISTINCT "audit_id") > 1
               ) "s"`,
        values: [latestScanIds],
    })).rows[0];

    // Each scan's own page list, like getAuditSummaryFast, so the ratio
    // matches the scans the blockers came from
    const pages = (await db.query({
        text: `SELECT COALESCE(SUM(jsonb_array_length("pages")), 0)::int AS "count" FROM "scans" WHERE "id" = ANY($1::uuid[])`,
        values: [latestScanIds],
    })).rows[0];
    await db.clean();

    return {
        statusCode: 200,
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            tag,
            periodDays: days,
            audits: audits.map((audit) => ({
                id: audit.id,
                name: audit.name,
                interval: audit.interval,
                createdAt: audit.created_at,
                user: audit.user_name || audit.user_email ? { name: audit.user_name, email: audit.user_email } : null,
                urlCount: audit.url_count,
                blockerCount: audit.blocker_count,
                blockersAtStart: audit.blockers_at_start,
                lastCompleteScanAt: audit.last_complete_scan_at,
                latestScanStatus: audit.latest_scan_status,
                latestScanAt: audit.latest_scan_at,
            })),
            summary: {
                // Same definition as the chart's latest point
                totalBlockersCount: audits.reduce((sum, audit) => sum + (audit.blocker_count ?? 0), 0),
                latestScanBlockersCount: breakdown.total,
                uniqueBlockersCount: breakdown.unique_count,
                sharedBlockersCount: shared.count,
                urlsWithBlockersCount: breakdown.urls_with_blockers,
                scannedUrlsCount: pages.count,
                pdfBlockersCount: breakdown.pdf,
                htmlBlockersCount: breakdown.html,
            },
        }),
    };
}
