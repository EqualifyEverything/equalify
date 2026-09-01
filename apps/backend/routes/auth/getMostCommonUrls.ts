import { db, event, graphqlQuery } from "#src/utils";

interface PaginatedItemCount {
  key: string;
  count: number;
  total_count: number;
}

export const getMostCommonUrls = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page ?? "0", 10);
  const pageSize = parseInt((event.queryStringParameters as any).pageSize ?? "5", 10);
  // "all" counts every blocker row; "group" counts unique blockers per URL
  // (distinct content_hash_id); "hide" only counts blockers whose hash
  // appears exactly once in the latest scan.
  const duplicatesMode = (event.queryStringParameters as any).duplicates || "all";

  if (duplicatesMode === "group" || duplicatesMode === "hide") {
    // The Hasura function this route normally calls has no duplicates
    // support, so these modes run the equivalent SQL directly (same shape as
    // db/migrations/add_most_common_pagination.sql).
    const countExpr =
      duplicatesMode === "group"
        ? `COUNT(DISTINCT "b"."content_hash_id")::int`
        : `COUNT(*)::int`;
    const hideFilter =
      duplicatesMode === "hide"
        ? `AND "b"."content_hash_id" IN (
             SELECT "content_hash_id" FROM "blockers"
             WHERE "scan_id" = (SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1)
             GROUP BY "content_hash_id" HAVING COUNT(*) = 1
           )`
        : "";

    await db.connect();
    const rows = (
      await db.query({
        text: `SELECT "u"."url"::text AS "key", ${countExpr} AS "count", COUNT(*) OVER ()::int AS "total_count"
               FROM "blockers" "b"
               JOIN "urls" "u" ON "b"."url_id" = "u"."id"
               WHERE "b"."scan_id" = (SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1)
               ${hideFilter}
               GROUP BY "u"."url"
               ORDER BY 2 DESC
               LIMIT $2 OFFSET $3`,
        values: [auditId, pageSize, page * pageSize],
      })
    ).rows as PaginatedItemCount[];
    await db.clean();

    return {
      statusCode: 200,
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        items: rows.map(({ key, count }) => ({ key, count })),
        totalCount: rows[0]?.total_count ?? 0,
        page,
        pageSize,
      }),
    };
  }

  const query = {
    query: `query GetMostCommonUrls($audit_id: uuid!, $limit: Int!, $offset: Int!) {
  items: get_most_common_urls_paginated(
    args: { search_audit_id: $audit_id, row_limit: $limit, row_offset: $offset }
  ) {
    key
    count
    total_count
  }
}`,
    variables: {
      audit_id: auditId,
      limit: pageSize,
      offset: page * pageSize,
    },
  };
  const response = (await graphqlQuery(query)) as { items: PaginatedItemCount[] };
  // total_count is identical on every row (a window-function total); 0 rows
  // just means this audit currently has no URLs with blockers.
  const totalCount = response.items[0]?.total_count ?? 0;

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      items: response.items.map(({ key, count }) => ({ key, count })),
      totalCount,
      page,
      pageSize,
    }),
  };
};
