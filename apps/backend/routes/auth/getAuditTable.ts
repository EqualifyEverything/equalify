import { db, event, graphqlQuery } from '#src/utils';

export const getAuditTable = async () => {
    const auditId = event.queryStringParameters.id;
    const page = parseInt((event.queryStringParameters as any).page || '0', 10);
    const pageSize = parseInt((event.queryStringParameters as any).pageSize || '50', 10);
    
    // Parse multiple filter parameters (comma-separated)
    const tagsParam = (event.queryStringParameters as any).tags || null;
    const categoriesParam = (event.queryStringParameters as any).categories || null;
    const statusParam = (event.queryStringParameters as any).status || null;
    
    const tagFilters = tagsParam ? tagsParam.split(',').filter(Boolean) : [];
    const typeFilters = categoriesParam ? categoriesParam.split(',').filter(Boolean) : [];

    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [auditId],
    })).rows?.[0];
    const urls = (await db.query({
        text: `SELECT "id", "url", "type" FROM "urls" WHERE "audit_id" = $1`,
        values: [auditId],
    })).rows;
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
                            id: { _in: tagFilters }
                        }
                    }
                }
            }
        });
    }
    
    // Category filtering (OR condition - blocker has ANY of the selected categories)
    if (typeFilters.length > 0) {
        whereConditions.push({
            blocker_messages: {
                message: {
                    category: { _in: typeFilters }
                }
            }
        });
    }
    
    // Status filtering (equalified true/false)
    if (statusParam) {
        if (statusParam === 'active') {
            whereConditions.push({
                blocker_messages: {
                    blocker: {
                        equalified: { _eq: false }
                    }
                }
            });
        } else if (statusParam === 'fixed') {
            whereConditions.push({
                blocker_messages: {
                    blocker: {
                        equalified: { _eq: true }
                    }
                }
            });
        }
    }
    
    // Combine all conditions with AND
    const whereClause = whereConditions.length > 0 ? { _and: whereConditions } : {};

    // Query to get blockers from the latest scan with pagination
    const query = {
        query: `query ($audit_id: uuid!, $limit: Int!, $offset: Int!, $where: blockers_bool_exp!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      id
      created_at
      blockers(where: $where, limit: $limit, offset: $offset, order_by: {created_at: desc}) {
        id
        short_id
        created_at
        content
        url_id
        blocker_messages {
          id
          blocker {
            equalified
          }
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
            where: whereClause
        },
    };
    
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    console.log(JSON.stringify({ response }));

    // Get URL lookup map
    const urlMap = urls.reduce((acc, url) => { 
        acc[url.id] = { url: url.url, type: url.type};
        return acc;
    }, {} as Record<string, string>);

    const latestScan = response.audits_by_pk?.scans?.[0];
    const blockers = latestScan?.blockers || [];
    const totalCount = latestScan?.blockers_aggregate?.aggregate?.count || 0;
    const availableTags = response.tags || [];
    const availableCategories = response.messages || [];

    // Format the blockers data
    const formattedBlockers = blockers.map(blocker => {
        // Extract tags from blocker_messages -> message -> message_tags -> tag
        const tags = blocker.blocker_messages.flatMap(bm => 
            bm.message.message_tags?.map(mt => mt.tag).filter(Boolean) || []
        );
        
        const uniqueTags = Array.from(
            new Map(tags.map(tag => [tag.id, tag])).values()
        );

        // Extract categories from blocker_messages -> message -> category
        const categories = blocker.blocker_messages.map(bm => bm.message.category);
        const uniqueCategories = Array.from(new Set(categories));

        // Extract equalified status from blocker_messages -> blocker -> equalified
        const equalified = blocker.blocker_messages.length > 0 
            ? blocker.blocker_messages[0].blocker.equalified 
            : false;

        // Extract message contents
        const messages = blocker.blocker_messages.map(bm => 
            `${bm.message.category}: ${bm.message.content}`
        );

        return {
            id: blocker.id,
            short_id: blocker.short_id,
            created_at: blocker.created_at,
            url: urlMap[blocker.url_id].url || blocker.url_id,
            type: urlMap[blocker.url_id].type,
            url_id: blocker.url_id,
            content: blocker.content,
            equalified: equalified,
            messages: messages,
            tags: uniqueTags,
            categories: uniqueCategories,
        };
    });

    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
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
            availableTags,
            availableCategories: availableCategories.map((m: any) => m.category).filter(Boolean),
            filters: {
                tags: tagFilters,
                types: typeFilters,
                status: statusParam,
            },
        },
    };
}