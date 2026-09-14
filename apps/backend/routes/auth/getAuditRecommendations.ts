import { db, event } from "#src/utils";

// Recommendations: one row per unique blocker (identical normalized HTML, i.e.
// same content_hash_id) in the latest scan, ranked by how many places it
// appears. Fixing the representative fixes every occurrence at once, so this
// is the "what should I fix first" view.
//
// Query params:
//   id        audit id (required)
//   page      0-based page, default 0
//   pageSize  default 10
//   status    active (default) | ignored | all   — ignore state is per hash
//   repeat    all (default) | repeated | single  — appears on >1 row vs exactly once

const STATUS_FILTERS: Record<string, string> = {
  active: `AND NOT "ignored"`,
  ignored: `AND "ignored"`,
  all: "",
};

const REPEAT_FILTERS: Record<string, string> = {
  all: "",
  repeated: `AND "occurrences" > 1`,
  single: `AND "occurrences" = 1`,
};

export const getAuditRecommendations = async () => {
  const qs = (event.queryStringParameters as any) || {};
  const auditId = qs.id;
  const page = Math.max(0, parseInt(qs.page ?? "0", 10) || 0);
  const pageSize = Math.min(100, Math.max(1, parseInt(qs.pageSize ?? "10", 10) || 10));
  const status = STATUS_FILTERS[qs.status] !== undefined ? qs.status : "active";
  const repeat = REPEAT_FILTERS[qs.repeat] !== undefined ? qs.repeat : "all";

  // Shared CTE prefix: latest scan for the audit + the set of ignored hashes.
  const withLatestAndIgnored = `
    WITH latest AS (
      SELECT "id", "created_at" FROM "scans"
      WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1
    ),
    ignored_hashes AS (
      SELECT DISTINCT "content_hash_id" FROM "ignored_blockers"
      WHERE "audit_id" = $1 AND "content_hash_id" IS NOT NULL
    )`;

  await db.connect();

  // Headline numbers. The filtered_* counts apply the status filter but NOT
  // the repeat filter, so the three stat blocks (all / repeated / single)
  // always show what each block would select — the blocks ARE that filter.
  const stats = (
    await db.query({
      text: `${withLatestAndIgnored},
        groups AS (
          SELECT "content_hash_id", COUNT(*)::int AS "occurrences"
          FROM "blockers"
          WHERE "scan_id" = (SELECT "id" FROM latest)
          GROUP BY "content_hash_id"
        ),
        groups_with_status AS (
          SELECT "g".*, ("g"."content_hash_id" IN (SELECT "content_hash_id" FROM ignored_hashes)) AS "ignored"
          FROM groups "g"
        ),
        status_filtered AS (
          SELECT * FROM groups_with_status WHERE TRUE ${STATUS_FILTERS[status]}
        )
        SELECT
          (SELECT "created_at" FROM latest) AS "scan_date",
          (SELECT COALESCE(SUM("occurrences"), 0) FROM groups)::int AS "total_blockers",
          (SELECT COUNT(*) FROM groups)::int AS "unique_blockers",
          (SELECT COUNT(*) FROM groups_with_status WHERE NOT "ignored")::int AS "active_unique_blockers",
          (SELECT COUNT(*) FROM groups WHERE "occurrences" > 1)::int AS "repeated_blockers",
          (
            SELECT COUNT(DISTINCT COALESCE("url_id"::text, "url_text")) FROM "blockers"
            WHERE "scan_id" = (SELECT "id" FROM latest)
          )::int AS "pages_with_blockers",
          (SELECT COUNT(*) FROM status_filtered)::int AS "filtered_unique",
          (SELECT COUNT(*) FROM status_filtered WHERE "occurrences" > 1)::int AS "filtered_repeated",
          (SELECT COUNT(*) FROM status_filtered WHERE "occurrences" = 1)::int AS "filtered_single",
          (SELECT COALESCE(SUM("occurrences"), 0) FROM status_filtered)::int AS "filtered_total_occurrences",
          (SELECT COALESCE(SUM("occurrences"), 0) FROM status_filtered WHERE "occurrences" > 1)::int AS "filtered_repeated_occurrences"`,
      values: [auditId],
    })
  ).rows[0];

  const items = (
    await db.query({
      text: `${withLatestAndIgnored},
        groups AS (
          SELECT
            "b"."content_hash_id",
            COUNT(*)::int AS "occurrences",
            COUNT(DISTINCT COALESCE("u"."url", "b"."url_text", 'Unknown URL'))::int AS "url_count",
            (ARRAY_AGG(DISTINCT COALESCE("u"."url", "b"."url_text", 'Unknown URL')))[1:10] AS "urls",
            (ARRAY_AGG("b"."id" ORDER BY "b"."created_at" DESC))[1] AS "representative_id",
            ("b"."content_hash_id" IN (SELECT "content_hash_id" FROM ignored_hashes)) AS "ignored"
          FROM "blockers" "b"
          LEFT JOIN "urls" "u" ON "b"."url_id" = "u"."id"
          WHERE "b"."scan_id" = (SELECT "id" FROM latest)
          GROUP BY "b"."content_hash_id"
        ),
        filtered AS (
          SELECT * FROM groups
          WHERE TRUE ${STATUS_FILTERS[status]} ${REPEAT_FILTERS[repeat]}
        ),
        page AS (
          SELECT *, COUNT(*) OVER ()::int AS "total_count"
          FROM filtered
          ORDER BY "occurrences" DESC, "url_count" DESC, "content_hash_id" ASC
          LIMIT $2 OFFSET $3
        )
        SELECT
          "p"."content_hash_id", "p"."occurrences", "p"."url_count", "p"."urls",
          "p"."ignored", "p"."total_count",
          "rb"."id", "rb"."short_id", "rb"."content",
          COALESCE("ru"."url", "rb"."url_text", 'Unknown URL') AS "url",
          COALESCE("ru"."type", 'unknown') AS "type",
          "m"."content" AS "message",
          "m"."category",
          COALESCE("t"."tags", '[]'::json) AS "tags"
        FROM page "p"
        JOIN "blockers" "rb" ON "rb"."id" = "p"."representative_id"
        LEFT JOIN "urls" "ru" ON "ru"."id" = "rb"."url_id"
        LEFT JOIN LATERAL (
          SELECT "m"."id", "m"."content", "m"."category"
          FROM "blocker_messages" "bm"
          JOIN "messages" "m" ON "m"."id" = "bm"."message_id"
          WHERE "bm"."blocker_id" = "rb"."id"
          LIMIT 1
        ) "m" ON TRUE
        LEFT JOIN LATERAL (
          SELECT json_agg(json_build_object('id', "t"."id", 'content', "t"."content")) AS "tags"
          FROM "message_tags" "mt"
          JOIN "tags" "t" ON "t"."id" = "mt"."tag_id"
          WHERE "mt"."message_id" = "m"."id"
        ) "t" ON TRUE
        ORDER BY "p"."occurrences" DESC, "p"."url_count" DESC, "p"."content_hash_id" ASC`,
      values: [auditId, pageSize, page * pageSize],
    })
  ).rows;

  await db.clean();

  const totalCount = items[0]?.total_count ?? 0;

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      audit_id: auditId,
      scan_date: stats?.scan_date ?? null,
      stats: {
        totalBlockers: stats?.total_blockers ?? 0,
        uniqueBlockers: stats?.unique_blockers ?? 0,
        activeUniqueBlockers: stats?.active_unique_blockers ?? 0,
        repeatedBlockers: stats?.repeated_blockers ?? 0,
        pagesWithBlockers: stats?.pages_with_blockers ?? 0,
        // Counts under the current status filter, one per stat block
        filteredUnique: stats?.filtered_unique ?? 0,
        filteredRepeated: stats?.filtered_repeated ?? 0,
        filteredSingle: stats?.filtered_single ?? 0,
        // How many blocker occurrences the repeated groups account for, so the
        // UI can say "fix these 3 and 7 of your 50 blockers go away"
        filteredTotalOccurrences: stats?.filtered_total_occurrences ?? 0,
        filteredRepeatedOccurrences: stats?.filtered_repeated_occurrences ?? 0,
      },
      items: items.map((row: any) => ({
        id: row.id,
        short_id: row.short_id,
        content_hash_id: row.content_hash_id,
        occurrences: row.occurrences,
        urlCount: row.url_count,
        urls: row.urls ?? [],
        ignored: row.ignored,
        url: row.url,
        type: row.type,
        content: row.content,
        message: row.message ?? "No message",
        category: row.category ?? "unknown",
        tags: row.tags ?? [],
      })),
      pagination: {
        page,
        pageSize,
        totalCount,
        totalPages: Math.ceil(totalCount / pageSize),
      },
      filters: { status, repeat },
    }),
  };
};
