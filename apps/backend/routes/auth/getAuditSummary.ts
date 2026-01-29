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
  data: {
    blockers: AuditSummaryRespBlocker[];
  };
}

// processing types
interface FlattenedData {
  url: string;
  message: string;
  tags: string[];
  category: string;
}

interface ItemCount {
  key: string;
  count: number;
}

export const getAuditSummary = async () => {
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
    query: `query ($audit_id: uuid!) {
                blockers( where: { audit_id: { _eq: $audit_id } } ) {
                    blocker_messages {
                        message {
                            content
                            message_tags {
                                tag {
                                    content
                                }
                            }
                            category
                        }
                    }
                    url {
                        url
                    }
                }
            }`,
    variables: {
      audit_id: auditId,
    },
  };
  const response = (await graphqlQuery(query)) as AuditSummaryResp;
  console.log(JSON.stringify({ response }));

  const flattened = response.data.blockers.map((item) => {
    return {
      url: item.url.url,
      message: item.blocker_messages[0].message.content,
      tags: item.blocker_messages[0].message.message_tags.map(
        (item) => item.tag.content,
      ),
      category: item.blocker_messages[0].message.category,
    };
  });

  // X pages have accessibility errors.
  const urlsWithBlockersCount = new Set(flattened.map((item) => item.url)).size;

  // Pages with most errors:
  const urlsWithMostErrors = getMostCommon(
    flattened,
    "url",
    mostCommonUrlsLimit,
  );

  // The most common accessibility errors: | Table |
  const mostCommonErrors = getMostCommon(
    flattened,
    "message",
    mostCommonBlockersLimit,
  );

  // most common category
  const mostCommonCategory = getMostCommon(
    flattened,
    "category",
    mostCommonCategoriesLimit,
  );

  // most common tags
  const mostCommonTags = getMostCommonTags(
    flattened,
    mostCommonTagsLimit
  )

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: {
      urlsWithBlockersCount,
      urlsWithMostErrors,
      mostCommonErrors,
      mostCommonCategory,
      mostCommonTags
    },
  };
};

const getMostCommon = (
  data: FlattenedData[],
  target: string,
  limit: number = 5,
): ItemCount[] => {
  const frequencyMap = data.reduce(
    (acc: Record<string, number>, item: FlattenedData) => {
      const a = item[target];
      acc[a] = (acc[a] || 0) + 1;
      return acc;
    },
    {},
  );

  return Object.entries(frequencyMap)
    .map(([key, count]): ItemCount => ({ key, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, limit);
};

const getMostCommonTags = (
  data: FlattenedData[],
  limit: number = 5,
): ItemCount[] => {
  const frequencyMap: Record<string, number> = {};
  data.forEach((item) => {
    item.tags.forEach((tag) => {
      frequencyMap[tag] = (frequencyMap[tag] || 0) + 1;
    });
  });

  return Object.entries(frequencyMap)
    .map(([key, count]): ItemCount => ({ key, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, limit);
};
