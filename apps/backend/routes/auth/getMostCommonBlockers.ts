import { db, event, graphqlQuery } from "#src/utils";

interface PaginatedItemCount {
  key: string;
  count: number;
  total_count: number;
}

export const getMostCommonBlockers = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page ?? "0", 10);
  const pageSize = parseInt((event.queryStringParameters as any).pageSize ?? "5", 10);
  // "all" counts every blocker row; "group" counts unique blockers per
  // message (distinct content_hash_id); "hide" only counts blockers whose
  // hash appears exactly once in the latest scan.
  const duplicatesMode = (event.queryStringParameters as any).duplicates || "all";

  let items: PaginatedItemCount[];
  let totalCount: number;

  if (duplicatesMode === "group" || duplicatesMode === "hide") {
    // The Hasura function this route normally calls has no duplicates
    // support, so these modes run the equivalent SQL directly (same shape as
    // db/migrations/add_most_common_pagination.sql).
    const countExpr =
      duplicatesMode === "group"
        ? `COUNT(DISTINCT "b"."content_hash_id")::int`
        : `COUNT(DISTINCT "b"."id")::int`;
    const hideFilter =
      duplicatesMode === "hide"
        ? `AND "b"."content_hash_id" IN (
             SELECT "content_hash_id" FROM "blockers"
             WHERE "scan_id" = (SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1)
             GROUP BY "content_hash_id" HAVING COUNT(*) = 1
           )`
        : "";

    await db.connect();
    items = (
      await db.query({
        text: `SELECT "m"."content"::text AS "key", ${countExpr} AS "count", COUNT(*) OVER ()::int AS "total_count"
               FROM "blockers" "b"
               JOIN "blocker_messages" "bm" ON "b"."id" = "bm"."blocker_id"
               JOIN "messages" "m" ON "bm"."message_id" = "m"."id"
               WHERE "b"."scan_id" = (SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1)
               ${hideFilter}
               GROUP BY "m"."content"
               ORDER BY 2 DESC
               LIMIT $2 OFFSET $3`,
        values: [auditId, pageSize, page * pageSize],
      })
    ).rows as PaginatedItemCount[];
    await db.clean();
    totalCount = items[0]?.total_count ?? 0;
  } else {
    const query = {
      query: `query GetMostCommonBlockers($audit_id: uuid!, $limit: Int!, $offset: Int!) {
  items: get_most_common_messages_paginated(
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
    items = response.items;
    // total_count is identical on every row (a window-function total); 0 rows
    // just means this audit currently has no blockers.
    totalCount = items[0]?.total_count ?? 0;
  }

  // Most common blockers are grouped by message content, but the Detailed View
  // can only be filtered by category — look up each content's category so the
  // summary table can link straight into a filtered Detailed View.
  const contents = items.map((item) => item.key);
  const categoriesResponse = contents.length > 0
    ? ((await graphqlQuery({
        query: `query GetMessageCategories($contents: [String!]) {
  messages(where: { content: { _in: $contents } }, distinct_on: content, order_by: { content: asc }) {
    content
    category
  }
}`,
        variables: { contents },
      })) as { messages: { content: string; category: string }[] })
    : { messages: [] };
  const contentToCategory = new Map(
    categoriesResponse.messages.map((m) => [m.content, m.category])
  );

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      items: items.map((item) => ({
        key: item.key,
        count: item.count,
        category: contentToCategory.get(item.key) ?? null,
      })),
      totalCount,
      page,
      pageSize,
    }),
  };
};
