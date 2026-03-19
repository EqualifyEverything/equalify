import { db, event, graphqlQuery } from "#src/utils";

// db resp types
interface AuditSummaryRespBlockerMessagesTag {
  tag: {
    content: string;
  };
}

interface AuditSummaryRespBlockerMessages {
  message: {
    content: string;
    message_tags: AuditSummaryRespBlockerMessagesTag[];
    category: string;
  };
}

interface AuditSummaryRespBlocker {
  blocker_messages: AuditSummaryRespBlockerMessages[];
  url: {
    url: string;
  };
}

interface AuditSummaryResp {
    blockers: AuditSummaryRespBlocker[];
}


export const getAuditSummaryFast = async () => {
  
  const start = performance.now();
  const auditId = (event.queryStringParameters as any).id;
  const mostCommonUrlsLimit =
    (event.queryStringParameters as any).mostCommonUrlsLimit ?? 5;
  const mostCommonBlockersLimit =
    (event.queryStringParameters as any).mostCommonBlockersLimit ?? 5;
  const mostCommonCategoriesLimit =
    (event.queryStringParameters as any).mostCommonCategoriesLimit ?? 3;
  const mostCommonTagsLimit =
    (event.queryStringParameters as any).mostCommonTagsLimit ?? 3;

  const query = {
    query: `query GetAuditStats($audit_id: uuid!, $urlLimit: Int!, $msgLimit: Int!) {
              unique_urls: blocker_summary_view_aggregate(
                distinct_on: [url],
                where: { audit_id: { _eq: $audit_id } }
              ) {
                aggregate {
                  count
                }
              }
            }`,
    variables: {
      audit_id: auditId,
      urlLimit: mostCommonUrlsLimit,
      msgLimit: mostCommonBlockersLimit
    },
  };
  const response = (await graphqlQuery(query)) as AuditSummaryResp;
  //console.log(JSON.stringify({ response }));

  const urlFreq: Record<string, number> = {};
  const msgFreq: Record<string, number> = {};
  const catFreq: Record<string, number> = {};
  const tagFreq: Record<string, number> = {};
  let uniqueUrlCount = 0;
  const urlSeen = new Set<string>();

  // SINGLE PASS: We touch each piece of data exactly once
  for (const blocker of response.blockers) {
    const url = blocker.url.url;
    
    // Track unique URLs
    if (!urlSeen.has(url)) {
      urlSeen.add(url);
      uniqueUrlCount++;
    }

    urlFreq[url] = (urlFreq[url] || 0) + 1;

    // Process nested messages (assuming at least one exists)
    const firstMsg = blocker.blocker_messages[0]?.message;
    if (firstMsg) {
      msgFreq[firstMsg.content] = (msgFreq[firstMsg.content] || 0) + 1;
      catFreq[firstMsg.category] = (catFreq[firstMsg.category] || 0) + 1;

      for (const t of firstMsg.message_tags) {
        const tag = t.tag.content;
        tagFreq[tag] = (tagFreq[tag] || 0) + 1;
      }
    }
  }

  // Reusable helper for sorting/slicing
  const getTop = (freqMap: Record<string, number>, limit: number) => 
    Object.entries(freqMap)
      .map(([key, count]) => ({ key, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, limit);

      
  const end = performance.now();
  return {
    statusCode: 200,
    body: {
      urlsWithBlockersCount: uniqueUrlCount,
      urlsWithMostErrors: getTop(urlFreq, mostCommonUrlsLimit),
      mostCommonErrors: getTop(msgFreq, mostCommonBlockersLimit),
      mostCommonCategory: getTop(catFreq, mostCommonCategoriesLimit),
      mostCommonTags: getTop(tagFreq, mostCommonTagsLimit),
      executionTime: end-start
    },
  };
};
