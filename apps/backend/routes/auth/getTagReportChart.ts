import { db, event, parseTagReportDays, tagReportRangeStart, validateUuid } from "#src/utils";

//
// Tag Report chart: combined blockers per day across the tag's audits.
//
// For each day, each audit contributes its latest *complete* scan's frozen
// blocker_count as of the end of that day (carried forward between scans,
// zero before its first scan), and those are summed. Days are UTC, matching
// getAuditChart.
//
// GET /auth/getTagReportChart?id=<tagId>&days=<range>
//

export const getTagReportChart = async () => {
    const params = (event.queryStringParameters ?? {}) as any;
    const tagId = params.id;
    if (!validateUuid(tagId)) {
        return { statusCode: 400, body: JSON.stringify({ error: "Invalid tag" }) };
    }
    const days = parseTagReportDays(params.days);
    const rangeStart = tagReportRangeStart(days);

    await db.connect();
    const rows = (await db.query({
        text: `WITH "members" AS (
                   SELECT "a"."id" FROM "audit_tag_assignments" "x"
                   JOIN "audits" "a" ON "a"."id"="x"."audit_id"
                   WHERE "x"."tag_id"=$1 AND "a"."interval" <> 'Quick Scan'
               ),
               "chart_days" AS (
                   SELECT ($2::timestamptz + make_interval(days => "i")) AS "day_start",
                          ($2::timestamptz + make_interval(days => "i" + 1)) AS "day_end"
                   FROM generate_series(0, $3::int - 1) AS "i"
               ),
               "totals" AS (
                   SELECT "d"."day_start",
                          COALESCE(SUM("s"."blocker_count"), 0)::int AS "blockers",
                          COUNT("s"."blocker_count")::int AS "audits_with_data"
                   FROM "chart_days" "d"
                   LEFT JOIN "members" "m" ON true
                   LEFT JOIN LATERAL (
                       SELECT "blocker_count" FROM "scans"
                       WHERE "audit_id"="m"."id" AND "status"='complete' AND "created_at" < "d"."day_end"
                       ORDER BY "created_at" DESC LIMIT 1
                   ) "s" ON true
                   GROUP BY "d"."day_start"
               ),
               "day_scans" AS (
                   SELECT "d"."day_start", COUNT(*)::int AS "scans_completed", MAX("sc"."created_at") AS "last_scan_at"
                   FROM "chart_days" "d"
                   JOIN "scans" "sc" ON "sc"."created_at" >= "d"."day_start" AND "sc"."created_at" < "d"."day_end"
                   WHERE "sc"."status"='complete' AND "sc"."audit_id" IN (SELECT "id" FROM "members")
                   GROUP BY "d"."day_start"
               )
               SELECT to_char("t"."day_start" AT TIME ZONE 'UTC', 'YYYY-MM-DD') AS "date",
                      "t"."blockers", "t"."audits_with_data",
                      COALESCE("ds"."scans_completed", 0) AS "scans_completed", "ds"."last_scan_at"
               FROM "totals" "t"
               LEFT JOIN "day_scans" "ds" ON "ds"."day_start"="t"."day_start"
               ORDER BY "t"."day_start"`,
        values: [tagId, rangeStart.toISOString(), days],
    })).rows;
    await db.clean();

    return {
        statusCode: 200,
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
            tag_id: tagId,
            period_days: days,
            // `timestamp` is only set on days with a completed scan, which is
            // what the chart's dots key off (see ChartCustomizedDot)
            data: rows.map((row) => ({
                date: row.date,
                blockers: row.blockers,
                auditsWithData: row.audits_with_data,
                scansCompleted: row.scans_completed,
                timestamp: row.last_scan_at,
            })),
        }),
    };
}
