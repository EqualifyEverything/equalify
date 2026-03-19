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
  const mostCommonUrlsLimit =
    (event.queryStringParameters as any).mostCommonUrlsLimit ?? 5;
  const mostCommonBlockersLimit =
    (event.queryStringParameters as any).mostCommonBlockersLimit ?? 5;
  /* const mostCommonCategoriesLimit =
    (event.queryStringParameters as any).mostCommonCategoriesLimit ?? 3;
   */
  const mostCommonTagsLimit =
    (event.queryStringParameters as any).mostCommonTagsLimit ?? 3;

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

  const end = performance.now();
  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: {
      urlsWithBlockersCount: response.unique_url_stats.aggregate.count,
      urlsWithMostErrors: response.mostCommonUrls,
      mostCommonErrors: response.mostCommonBlockers,
      mostCommonTags: response.mostCommonTags,
      executionTime: end - start
    },
  };
};
