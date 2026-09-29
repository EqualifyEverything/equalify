import { db } from "./db";
import { chunk } from "./chunk";
import { validateShortId } from "./validateShortId";

//
// Blockers for one specific (usually non-latest) scan — powers the Detailed
// view's scan-history picker and its CSV export. Once a newer scan lands,
// runEveryDay moves the old scan's rows from blockers/blocker_messages into
// stale_blockers/stale_blocker_messages; until that sweep runs they're still in
// the active tables, so every read unions both (a row only ever lives in one).
// Plain SQL rather than Hasura since the stale tables aren't tracked there.
//
// Expects the caller to have already run db.connect() (and to db.clean()).
//

export interface HistoricalBlockerFilters {
  contentType?: string;
  tags?: string[];
  categories?: string[];
  status?: string | null;
  searchString?: string;
  sortBy?: string;
  sortOrder?: string;
}

export interface HistoricalBlocker {
  id: string;
  short_id: string;
  content_hash_id: string;
  created_at: string;
  content: string;
  url_id: string | null;
  url: string;
  type: string;
  ignored: boolean;
  occurrences: number;
  messages: string[];
  tags: { id: string; content: string }[];
  categories: string[];
}

// stale_blockers has no url_text column (runEveryDay doesn't carry it over),
// so stale rows fall back to the live urls join only.
const SCAN_ROWS_CTE = `
  WITH scan_blockers AS (
    SELECT "id", "short_id", "content_hash_id", "created_at", "content", "url_id", "url_text"
    FROM "blockers" WHERE "scan_id" = $1 AND "audit_id" = $2
    UNION ALL
    SELECT "id", "short_id", "content_hash_id", "created_at", "content", "url_id", NULL::text
    FROM "stale_blockers" WHERE "scan_id" = $1 AND "audit_id" = $2
  ),
  scan_rows AS (
    SELECT b."id", b."short_id", b."content_hash_id", b."created_at", b."content", b."url_id",
           COALESCE(u."url", b."url_text", 'Unknown URL') AS "url",
           COALESCE(u."type", 'unknown') AS "type",
           -- Ignores are hash-wide and carry forward, so judge historical rows
           -- by the audit's current ignore list (blocker_id catches legacy rows
           -- with a NULL hash).
           (b."content_hash_id" IN (SELECT "content_hash_id" FROM "ignored_blockers" WHERE "audit_id" = $2 AND "content_hash_id" IS NOT NULL)
             OR b."id" IN (SELECT "blocker_id" FROM "ignored_blockers" WHERE "audit_id" = $2)) AS "ignored",
           COUNT(*) OVER (PARTITION BY b."content_hash_id")::int AS "occurrences"
    FROM scan_blockers b
    LEFT JOIN "urls" u ON u."id" = b."url_id"
  )`;

// Message ids for the blocker in the current row, from whichever link table holds them.
const ROW_MESSAGE_IDS = `(
  SELECT "message_id" FROM "blocker_messages" WHERE "blocker_id" = r."id"
  UNION ALL
  SELECT "message_id" FROM "stale_blocker_messages" WHERE "blocker_id" = r."id"
)`;

export const getHistoricalBlockers = async ({
  auditId,
  scanId,
  filters,
  limit,
  offset = 0,
}: {
  auditId: string;
  scanId: string;
  filters: HistoricalBlockerFilters;
  /** Omit to return every matching row (CSV export). */
  limit?: number;
  offset?: number;
}) => {
  const values: any[] = [scanId, auditId];
  const param = (value: any) => {
    values.push(value);
    return `$${values.length}`;
  };

  // Keyed by dimension so the count query can drop its own filter — status
  // counts ignore the status filter and type counts ignore the type filter,
  // matching getAuditTable's baseWhere / contentTypeBaseWhere.
  const conditions: { key: "status" | "type" | "other"; sql: string }[] = [];

  const contentType = filters.contentType?.toLowerCase();
  if (contentType === "html" || contentType === "pdf") {
    conditions.push({ key: "type", sql: `r."type" = ${param(contentType)}` });
  }
  if (filters.tags?.length) {
    conditions.push({
      key: "other",
      sql: `EXISTS (SELECT 1 FROM "message_tags" mt WHERE mt."message_id" IN ${ROW_MESSAGE_IDS} AND mt."tag_id" = ANY(${param(filters.tags)}::uuid[]))`,
    });
  }
  if (filters.categories?.length) {
    conditions.push({
      key: "other",
      sql: `EXISTS (SELECT 1 FROM "messages" m WHERE m."id" IN ${ROW_MESSAGE_IDS} AND m."category" = ANY(${param(filters.categories)}::text[]))`,
    });
  }
  if (filters.status === "active") {
    conditions.push({ key: "status", sql: `NOT r."ignored"` });
  } else if (filters.status === "ignored") {
    conditions.push({ key: "status", sql: `r."ignored"` });
  }
  const searchString = filters.searchString ?? "";
  if (searchString !== "") {
    if (validateShortId(searchString)) {
      conditions.push({ key: "other", sql: `r."short_id" = ${param(searchString)}` });
    } else {
      // Same semantics as buildUrlSearchClause: quoted = exact, else substring.
      const quotedMatch = searchString.match(/^"(.*)"$/);
      const pattern = quotedMatch ? quotedMatch[1] : `%${searchString}%`;
      conditions.push({ key: "other", sql: `r."url" ILIKE ${param(pattern)}` });
    }
  }

  // Counts: evaluate each condition once per row into a flag column (OFFSET 0
  // keeps Postgres from inlining the subquery and re-running the EXISTS
  // subqueries once per FILTER), then aggregate over the flags.
  const flagColumns = conditions.map((c, i) => `(${c.sql}) AS "c${i}"`).join(", ");
  const flagsWhere = (exclude: string[]) => {
    const parts = conditions
      .map((c, i) => (exclude.includes(c.key) ? null : `f."c${i}"`))
      .filter(Boolean);
    return parts.length ? parts.join(" AND ") : "TRUE";
  };
  const counts = (
    await db.query({
      text: `${SCAN_ROWS_CTE}
        SELECT
          COUNT(*) FILTER (WHERE ${flagsWhere([])})::int AS "total",
          COUNT(*) FILTER (WHERE ${flagsWhere(["status"])})::int AS "status_all",
          COUNT(*) FILTER (WHERE ${flagsWhere(["status"])} AND NOT f."ignored")::int AS "status_active",
          COUNT(*) FILTER (WHERE ${flagsWhere(["status"])} AND f."ignored")::int AS "status_ignored",
          COUNT(*) FILTER (WHERE ${flagsWhere(["type"])})::int AS "type_all",
          COUNT(*) FILTER (WHERE ${flagsWhere(["type"])} AND f."type" = 'html')::int AS "type_html",
          COUNT(*) FILTER (WHERE ${flagsWhere(["type"])} AND f."type" = 'pdf')::int AS "type_pdf"
        FROM (SELECT r."ignored", r."type"${flagColumns ? `, ${flagColumns}` : ""} FROM scan_rows r OFFSET 0) f`,
      values,
    })
  ).rows[0];

  const direction = filters.sortOrder === "asc" ? "ASC" : "DESC";
  const orderBy =
    filters.sortBy === "url"
      ? `r."url" ${direction}, r."id"`
      : `r."created_at" ${direction}, r."id"`;
  const pageValues = [...values];
  const pageClause =
    limit !== undefined
      ? `LIMIT $${pageValues.push(limit)} OFFSET $${pageValues.push(offset)}`
      : "";
  const rows = (
    await db.query({
      text: `${SCAN_ROWS_CTE}
        SELECT r.* FROM scan_rows r
        WHERE ${conditions.length ? conditions.map((c) => c.sql).join(" AND ") : "TRUE"}
        ORDER BY ${orderBy}
        ${pageClause}`,
      values: pageValues,
    })
  ).rows;

  // Messages + tags for the returned rows, in id batches so a full-scan export
  // doesn't send one enormous array parameter.
  const detailsByBlocker = new Map<
    string,
    { messages: Map<string, { content: string; category: string }>; tags: Map<string, string> }
  >();
  for (const ids of chunk(rows.map((row) => row.id), 1000)) {
    const detailRows = (
      await db.query({
        text: `SELECT bm."blocker_id", m."id" AS "message_id", m."content", m."category",
                      t."id" AS "tag_id", t."content" AS "tag_content"
               FROM (
                 SELECT "blocker_id", "message_id" FROM "blocker_messages" WHERE "blocker_id" = ANY($1::uuid[])
                 UNION ALL
                 SELECT "blocker_id", "message_id" FROM "stale_blocker_messages" WHERE "blocker_id" = ANY($1::uuid[])
               ) bm
               JOIN "messages" m ON m."id" = bm."message_id"
               LEFT JOIN "message_tags" mt ON mt."message_id" = m."id"
               LEFT JOIN "tags" t ON t."id" = mt."tag_id"`,
        values: [ids],
      })
    ).rows;
    for (const detail of detailRows) {
      if (!detailsByBlocker.has(detail.blocker_id)) {
        detailsByBlocker.set(detail.blocker_id, { messages: new Map(), tags: new Map() });
      }
      const entry = detailsByBlocker.get(detail.blocker_id)!;
      entry.messages.set(detail.message_id, { content: detail.content, category: detail.category });
      if (detail.tag_id) entry.tags.set(detail.tag_id, detail.tag_content);
    }
  }

  const blockers: HistoricalBlocker[] = rows.map((row) => {
    const details = detailsByBlocker.get(row.id);
    const messages = Array.from(details?.messages.values() ?? []);
    return {
      id: row.id,
      short_id: row.short_id,
      content_hash_id: row.content_hash_id,
      created_at: row.created_at,
      content: row.content,
      url_id: row.url_id,
      url: row.url,
      type: row.type,
      ignored: row.ignored,
      occurrences: row.occurrences,
      messages: messages.map((m) => m.content),
      tags: Array.from(details?.tags ?? []).map(([id, content]) => ({ id, content })),
      categories: Array.from(new Set(messages.map((m) => m.category))),
    };
  });

  return {
    blockers,
    totalCount: counts?.total ?? 0,
    statusCounts: {
      all: counts?.status_all ?? 0,
      active: counts?.status_active ?? 0,
      ignored: counts?.status_ignored ?? 0,
    },
    typeCounts: {
      all: counts?.type_all ?? 0,
      html: counts?.type_html ?? 0,
      pdf: counts?.type_pdf ?? 0,
    },
  };
};
