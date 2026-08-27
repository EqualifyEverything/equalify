import { db, event, graphqlQuery } from "#src/utils";

interface ItemCount {
  key: string;
  count: number;
}

interface AuditSummaryResp {
  unique_url_stats: {
    aggregate: {
      count: number;
    };
  };
  mostCommonUrls: ItemCount[];
  mostCommonBlockers: ItemCount[];
  mostCommonTags: ItemCount[];
}

export const getAuditSummaryFast = async () => {
  
  const start = performance.now();
  const auditId = (event.queryStringParameters as any).id;
  const mostCommonUrlsLimit = parseInt(
    (event.queryStringParameters as any).mostCommonUrlsLimit ?? "5"
  );
  const mostCommonBlockersLimit = parseInt(
    (event.queryStringParameters as any).mostCommonBlockersLimit ?? "5"
  );
  /* const mostCommonCategoriesLimit = parseInt(
    (event.queryStringParameters as any).mostCommonCategoriesLimit ?? "3"
  ); */
  const mostCommonTagsLimit = parseInt(
    (event.queryStringParameters as any).mostCommonTagsLimit ?? "3"
  );

  const query = {
    query: `query GetFullAuditSummary(
  $audit_id: uuid!, 
  $urlLimit: Int, 
  $msgLimit: Int, 
  $tagLimit: Int
) {
  # 1. Total Unique URLs with blockers
  unique_url_stats: blocker_summary_view_aggregate(
    where: { audit_id: { _eq: $audit_id } }
  ) {
    aggregate {
      count(columns: url, distinct: true)
    }
  }

  mostCommonUrls: get_most_common_urls(
    args: { search_audit_id: $audit_id, row_limit: $urlLimit }
  ) {
    key
    count
  }

  mostCommonBlockers: get_most_common_messages(
    args: { search_audit_id: $audit_id, row_limit: $msgLimit }
  ) {
    key
    count
  }

  mostCommonTags: get_most_common_tags(
    args: { search_audit_id: $audit_id, row_limit: $tagLimit }
  ) {
    key
    count
  }
}`,
    variables: {
      audit_id: auditId,
      urlLimit: mostCommonUrlsLimit,
      msgLimit: mostCommonBlockersLimit,
      tagLimit: mostCommonTagsLimit
    },
  };
  const response = (await graphqlQuery(query)) as AuditSummaryResp;

  // Blockers-per-URL delta needs the actual URL count each scan ran against
  // (not the audit's current URL list, which can change between scans).
  // jsonb_array_length reads the element count off the jsonb container header
  // rather than walking the array, so this stays cheap even for scans with
  // thousands of pages.
  await db.connect();
  const recentScans = (
    await db.query({
      text: `SELECT "blocker_count", jsonb_array_length("pages") AS "pages_count"
             FROM "scans"
             WHERE "audit_id" = $1 AND "status" = 'complete'
             ORDER BY "created_at" DESC
             LIMIT 2`,
      values: [auditId],
    })
  ).rows as { blocker_count: number; pages_count: number }[];
  const [latestScan, previousScan] = recentScans;

  // Mirrors blocker_summary_view's own "latest scan" definition (most recent
  // scan regardless of status) so these counts stay consistent with
  // unique_url_stats above, which reads from that same view.
  const blockerTypeRows = (
    await db.query({
      text: `SELECT COALESCE("u"."type", 'html') AS "type", COUNT(*)::int AS "count"
             FROM "blockers" "b"
             LEFT JOIN "urls" "u" ON "b"."url_id" = "u"."id"
             WHERE "b"."scan_id" = (
               SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1
             )
             GROUP BY COALESCE("u"."type", 'html')`,
      values: [auditId],
    })
  ).rows as { type: string; count: number }[];
  await db.clean();

  const pdfBlockersCount = blockerTypeRows.find((row) => row.type === "pdf")?.count ?? 0;
  const htmlBlockersCount = blockerTypeRows.find((row) => row.type === "html")?.count ?? 0;

  // Most common blockers are grouped by message content, but the Detailed View
  // can only be filtered by category — look up each content's category so the
  // summary table can link straight into a filtered Detailed View.
  const contents = response.mostCommonBlockers.map((item) => item.key);
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
  const mostCommonErrors = response.mostCommonBlockers.map((item) => ({
    ...item,
    category: contentToCategory.get(item.key) ?? null,
  }));

  const end = performance.now();
  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      urlsWithBlockersCount: response.unique_url_stats.aggregate.count,
      urlsWithMostErrors: response.mostCommonUrls,
      mostCommonErrors,
      mostCommonTags: response.mostCommonTags,
      latestScan: latestScan
        ? { blockerCount: latestScan.blocker_count, pagesCount: latestScan.pages_count }
        : null,
      previousScan: previousScan
        ? { blockerCount: previousScan.blocker_count, pagesCount: previousScan.pages_count }
        : null,
      pdfBlockersCount,
      htmlBlockersCount,
      executionTime: end - start
    }),
  };
};
