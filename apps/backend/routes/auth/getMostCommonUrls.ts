import { event, graphqlQuery } from "#src/utils";

interface PaginatedItemCount {
  key: string;
  count: number;
  total_count: number;
}

export const getMostCommonUrls = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page ?? "0", 10);
  const pageSize = parseInt((event.queryStringParameters as any).pageSize ?? "5", 10);

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
