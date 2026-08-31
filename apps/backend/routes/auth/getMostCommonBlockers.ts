import { event, graphqlQuery } from "#src/utils";

interface PaginatedItemCount {
  key: string;
  count: number;
  total_count: number;
}

export const getMostCommonBlockers = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page ?? "0", 10);
  const pageSize = parseInt((event.queryStringParameters as any).pageSize ?? "5", 10);

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
  // total_count is identical on every row (a window-function total); 0 rows
  // just means this audit currently has no blockers.
  const totalCount = response.items[0]?.total_count ?? 0;

  // Most common blockers are grouped by message content, but the Detailed View
  // can only be filtered by category — look up each content's category so the
  // summary table can link straight into a filtered Detailed View.
  const contents = response.items.map((item) => item.key);
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
      items: response.items.map((item) => ({
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
