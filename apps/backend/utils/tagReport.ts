import { db } from "./db";

//
// Shared scope for the Tag Report routes (getTagReport*). A tag report covers
// the audits *currently* assigned the tag (quick scans excluded), so its
// history follows today's membership.
//
// Two different "latest" scans matter per audit:
// - latest_scan_id: the most recent scan whatever its status. Only this scan
//   still has rows in `blockers` (runEveryDay moves every other scan's rows to
//   stale_blockers), so the URL/blocker breakdowns read from it, matching the
//   Audit page.
// - the latest *complete* scan's frozen blocker_count, which the totals and
//   chart use so an audit that's mid-scan (or whose last scan failed) doesn't
//   drop out of the combined number.
//

export interface TagReportAudit {
    id: string;
    name: string;
    interval: string;
    created_at: string;
    user_name: string | null;
    user_email: string | null;
    url_count: number;
    latest_scan_id: string | null;
    latest_scan_status: string | null;
    latest_scan_at: string | null;
    blocker_count: number | null;
    last_complete_scan_at: string | null;
    blockers_at_start: number | null;
}

// Start of the UTC day `days - 1` days ago, i.e. the first day a `days`-long
// chart shows (matching getAuditChart's UTC date keys)
export const tagReportRangeStart = (days: number) => {
    const start = new Date();
    start.setUTCHours(0, 0, 0, 0);
    start.setUTCDate(start.getUTCDate() - (days - 1));
    return start;
}

// Expects db to already be connected. Returns null if the tag doesn't exist.
// With `days`, each audit also gets blockers_at_start: its latest complete
// count as of the end of the chart's first day (the chart's first point).
export const getTagReportScope = async (tagId: string, days?: number) => {
    const tag = (await db.query({
        text: `SELECT "id", "name" FROM "audit_tags" WHERE "id"=$1`,
        values: [tagId],
    })).rows?.[0] as { id: string; name: string } | undefined;
    if (!tag) return null;

    const firstDayEnd = days ? tagReportRangeStart(days) : null;
    firstDayEnd?.setUTCDate(firstDayEnd.getUTCDate() + 1);

    const audits = (await db.query({
        text: `SELECT "a"."id", "a"."name", "a"."interval", "a"."created_at",
                      "u"."name" AS "user_name", "u"."email" AS "user_email",
                      (SELECT COUNT(*)::int FROM "urls" WHERE "audit_id"="a"."id") AS "url_count",
                      "ls"."id" AS "latest_scan_id", "ls"."status" AS "latest_scan_status", "ls"."created_at" AS "latest_scan_at",
                      "lc"."blocker_count", "lc"."created_at" AS "last_complete_scan_at",
                      "st"."blocker_count" AS "blockers_at_start"
               FROM "audit_tag_assignments" "x"
               JOIN "audits" "a" ON "a"."id"="x"."audit_id"
               LEFT JOIN "users" "u" ON "u"."id"="a"."user_id"
               LEFT JOIN LATERAL (
                   SELECT "id", "status", "created_at" FROM "scans"
                   WHERE "audit_id"="a"."id" ORDER BY "created_at" DESC LIMIT 1
               ) "ls" ON true
               LEFT JOIN LATERAL (
                   SELECT "blocker_count", "created_at" FROM "scans"
                   WHERE "audit_id"="a"."id" AND "status"='complete' ORDER BY "created_at" DESC LIMIT 1
               ) "lc" ON true
               LEFT JOIN LATERAL (
                   SELECT "blocker_count" FROM "scans"
                   WHERE "audit_id"="a"."id" AND "status"='complete' AND $2::timestamptz IS NOT NULL AND "created_at" < $2::timestamptz
                   ORDER BY "created_at" DESC LIMIT 1
               ) "st" ON true
               WHERE "x"."tag_id"=$1 AND "a"."interval" <> 'Quick Scan'
               ORDER BY lower("a"."name")`,
        values: [tagId, firstDayEnd?.toISOString() ?? null],
    })).rows as TagReportAudit[];

    return {
        tag,
        audits,
        latestScanIds: audits.map((audit) => audit.latest_scan_id).filter(Boolean) as string[],
    };
}

// Parses the shared `days` query param (chart range), defaulting to 90
export const parseTagReportDays = (value: unknown) => {
    const days = parseInt(String(value ?? "90"), 10);
    return Number.isFinite(days) && days >= 1 && days <= 366 ? days : 90;
}
