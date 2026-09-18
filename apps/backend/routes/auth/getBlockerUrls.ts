import { db, event } from "#src/utils";

// The full, paginated list of every occurrence of a given blocker (grouped by
// content_hash_id) in the latest scan, one row per occurrence and ordered by
// URL — so the same URL appears more than once if the blocker shows up more
// than once on that page. This is what reconciles the "occurrences" count
// against "distinct URLs": they differ whenever a blocker repeats on a single
// page. Backs the "view all occurrences" drawer and its CSV export.
//
// Query params:
//   id                 audit id (required)
//   content_hash_id    blocker group id (required)
//   page               0-based page, default 0
//   pageSize           default 25, max 1000

export const getBlockerUrls = async () => {
  const qs = (event.queryStringParameters as any) || {};
  const auditId = qs.id;
  const contentHashId = qs.content_hash_id;
  const page = Math.max(0, parseInt(qs.page ?? "0", 10) || 0);
  const pageSize = Math.min(1000, Math.max(1, parseInt(qs.pageSize ?? "25", 10) || 25));

  await db.connect();

  const rows = (
    await db.query({
      text: `
        WITH latest AS (
          SELECT "id" FROM "scans"
          WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1
        ),
        occurrences AS (
          SELECT COALESCE("u"."url", "b"."url_text", 'Unknown URL') AS "url", "b"."id"
          FROM "blockers" "b"
          LEFT JOIN "urls" "u" ON "b"."url_id" = "u"."id"
          WHERE "b"."scan_id" = (SELECT "id" FROM latest)
            AND "b"."content_hash_id" = $2
        )
        SELECT "url", COUNT(*) OVER ()::int AS "total_count"
        FROM occurrences
        ORDER BY "url" ASC, "id" ASC
        LIMIT $3 OFFSET $4`,
      values: [auditId, contentHashId, pageSize, page * pageSize],
    })
  ).rows;

  await db.clean();

  const totalCount = rows[0]?.total_count ?? 0;

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      urls: rows.map((row: any) => row.url),
      pagination: {
        page,
        pageSize,
        totalCount,
        totalPages: Math.ceil(totalCount / pageSize),
      },
    }),
  };
};
