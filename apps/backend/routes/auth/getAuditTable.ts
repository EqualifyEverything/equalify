import { db, event, graphqlQuery } from "#src/utils";

export const getAuditTable = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page || "0", 10);
  const pageSize = parseInt(
    (event.queryStringParameters as any).pageSize || "50",
    10
  );
  const contentType = (event.queryStringParameters as any).contentType || "all";
  const sortBy = (event.queryStringParameters as any).sortBy || "created_at";
  const sortOrder = (event.queryStringParameters as any).sortOrder || "desc";

  // Parse multiple filter parameters (comma-separated)
  const tagsParam = (event.queryStringParameters as any).tags || null;
  const categoriesParam =
    (event.queryStringParameters as any).categories || null;
  const statusParam = (event.queryStringParameters as any).status || null;

  const tagFilters = tagsParam ? tagsParam.split(",").filter(Boolean) : [];
  const typeFilters = categoriesParam
    ? categoriesParam.split(",").filter(Boolean)
    : [];

  await db.connect();
  const audit = (
    await db.query({
      text: `SELECT * FROM "audits" WHERE "id" = $1`,
      values: [auditId],
    })
  ).rows?.[0];
  await db.clean();

  // Build the where clause with multiple filters
  const whereConditions: any[] = [];

  // Tag filtering (OR condition - blocker has ANY of the selected tags)
  if (tagFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: {
          message_tags: {
            tag: {
              id: { _in: tagFilters },
            },
          },
        },
      },
    });
  }

  // Category filtering (OR condition - blocker has ANY of the selected categories)
  if (typeFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: {
          category: { _in: typeFilters },
        },
      },
    });
  }

  // Status filtering ('ignore' field true/false)
  if (statusParam) {
    if (statusParam === "active") {
      whereConditions.push({
        blocker_messages: {
          blocker: {
            _not: { 
                ignored_blocker: { 
                    blocker_id: { 
                        _is_null: false 
                    } 
                } 
            },
          },
        },
      });
    } else if (statusParam === "ignored") {
      whereConditions.push({
        blocker_messages: {
          blocker: {
            ignored_blocker: {
              id: {
                _is_null: false,
              },
            },
          },
        },
      });
    }
  }

  // Combine all conditions with AND
  const whereClause =
    whereConditions.length > 0 ? { _and: whereConditions } : {};

  // Build order_by clause based on sortBy parameter
  let orderByClause;
  if (sortBy === "url") {
    // Sort by the related url table's url field
    orderByClause = { url: { url: sortOrder } };
  } else {
    // Default to sorting by created_at or other fields on the blocker table
    orderByClause = { created_at: sortOrder };
  }

  // Build where clauses for status counts (excluding status filter)
  // Get Where conditions without ignore conditions
  const baseWhereConditions = whereConditions.filter(
    (cond) => !(cond.blocker_messages?.blocker?.ignored_blocker || cond.blocker_messages?.blocker?._not?.ignored_blocker)
  );
  const baseWhereClause =
    baseWhereConditions.length > 0 ? { _and: baseWhereConditions } : {};

  const activeWhereClause = {
    _and: [
      ...baseWhereConditions,
      {
        blocker_messages: {
          blocker: {
            _not: { 
                ignored_blocker: { 
                    blocker_id: { 
                        _is_null: false 
                    } 
                } 
            },
          },
        },
      },
    ],
  };

  const ignoredWhereClause = {
    _and: [
      ...baseWhereConditions,
      {
        blocker_messages: {
          blocker: {
            ignored_blocker: {
              id: {
                _is_null: false,
              },
            },
          },
        },
      },
    ],
  };

  // Query to get blockers from the latest scan with pagination
  const query = {
    query: `query ($audit_id: uuid!, $limit: Int!, $offset: Int!, $where: blockers_bool_exp!, $order_by: [blockers_order_by!], $baseWhere: blockers_bool_exp!, $activeWhere: blockers_bool_exp!, $ignoredWhere: blockers_bool_exp!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      id
      created_at
      blockers(where: $where, limit: $limit, offset: $offset, order_by: $order_by) {
        id
        short_id
        created_at
        content
        url_id
        url {
          url
          type
        }
        blocker_messages {
          id
          message {
            id
            content
            category
            message_tags {
              tag {
                id
                content
              }
            }
          }
        }
      }
      blockers_aggregate(where: $where) {
        aggregate {
          count
        }
      }
      all_blockers_count: blockers_aggregate(where: $baseWhere) {
        aggregate {
          count
        }
      }
      active_blockers_count: blockers_aggregate(where: $activeWhere) {
        aggregate {
          count
        }
      }
      ignored_blockers_count: blockers_aggregate(where: $ignoredWhere) {
        aggregate {
          count
        }
      }
    }
  }
  tags(order_by: {content: asc}) {
    id
    content
  }
  messages(order_by: {category: asc}, distinct_on: category) {
    category
  }
}`,
    variables: {
      audit_id: auditId,
      limit: pageSize,
      offset: page * pageSize,
      where: whereClause,
      order_by: [orderByClause],
      baseWhere: baseWhereClause,
      activeWhere: activeWhereClause,
      ignoredWhere: ignoredWhereClause,
    },
  };

  console.log(JSON.stringify({ query }));
  const response = await graphqlQuery(query);
  console.log(JSON.stringify({ response }));

  const latestScan = response.audits_by_pk?.scans?.[0];
  const blockers = latestScan?.blockers || [];
  const totalCount = latestScan?.blockers_aggregate?.aggregate?.count || 0;
  const allBlockersCount =
    latestScan?.all_blockers_count?.aggregate?.count || 0;
  const activeBlockersCount =
    latestScan?.active_blockers_count?.aggregate?.count || 0;
  const ignoredBlockersCount =
    latestScan?.ignored_blockers_count?.aggregate?.count || 0;
  const availableTags = response.tags || [];
  const availableCategories = response.messages || [];

  // Format the blockers data
  let formattedBlockers = blockers.map((blocker) => {
    // Extract tags from blocker_messages -> message -> message_tags -> tag
    const tags = blocker.blocker_messages.flatMap(
      (bm) => bm.message.message_tags?.map((mt) => mt.tag).filter(Boolean) || []
    );

    const uniqueTags = Array.from(
      new Map(tags.map((tag) => [tag.id, tag])).values()
    );

    // Extract categories from blocker_messages -> message -> category
    const categories = blocker.blocker_messages.map(
      (bm) => bm.message.category
    );
    const uniqueCategories = Array.from(new Set(categories));

    // Extract equalified status from blocker_messages -> blocker -> equalified
    /* const equalified = blocker.blocker_messages.length > 0 
            ? blocker.blocker_messages[0].blocker.equalified 
            : false; */

    // Extract message contents
    const messages = blocker.blocker_messages.map(
      (bm) => `[${bm.message.category}] ${bm.message.content}`
    );

    return {
      id: blocker.id,
      short_id: blocker.short_id,
      created_at: blocker.created_at,
      url: blocker.url?.url || blocker.url_id,
      type: blocker.url?.type || "unknown",
      url_id: blocker.url_id,
      content: blocker.content,
      ignore: blocker.ignore,
      //equalified: equalified,
      messages: messages,
      tags: uniqueTags,
      categories: uniqueCategories,
    };
  });

  // filter for content type. We need to do it last because the type field is set by URL, not blocker
  if (
    contentType.toLowerCase() === "html" ||
    contentType.toLowerCase() === "pdf"
  ) {
    formattedBlockers = formattedBlockers.filter((blocker) => {
      return blocker.type.toLowerCase() === contentType.toLowerCase();
    });
  }

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: {
      audit_id: auditId,
      audit_name: audit?.name,
      scan_date: latestScan?.created_at,
      blockers: formattedBlockers,
      pagination: {
        page,
        pageSize,
        totalCount,
        totalPages: Math.ceil(totalCount / pageSize),
      },
      statusCounts: {
        all: allBlockersCount,
        active: activeBlockersCount,
        ignored: ignoredBlockersCount,
      },
      availableTags,
      availableCategories: availableCategories
        .map((m: any) => m.category)
        .filter(Boolean),
      filters: {
        tags: tagFilters,
        types: typeFilters,
        status: statusParam,
      },
    },
  };
};
