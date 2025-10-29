import { db, event, graphqlQuery } from '#src/utils';

export const getAuditTable = async () => {
    const auditId = event.queryStringParameters.id;
    const page = parseInt((event.queryStringParameters as any).page || '0', 10);
    const pageSize = parseInt((event.queryStringParameters as any).pageSize || '50', 10);
    const tagFilter = (event.queryStringParameters as any).tag || null; // tag ID to filter by

    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [auditId],
    })).rows?.[0];
    const urls = (await db.query({
        text: `SELECT "id", "url" FROM "urls" WHERE "audit_id" = $1`,
        values: [auditId],
    })).rows;
    await db.clean();

    // Build the where clause for tag filtering
    let whereClause = {};
    if (tagFilter) {
        whereClause = {
            blocker_type_blockers: {
                blocker_type: {
                    blocker_type_tags: {
                        blocker_tag: {
                            id: { _eq: tagFilter }
                        }
                    }
                }
            }
        };
    }

    // Query to get blockers from the latest scan with pagination
    const query = {
        query: `query ($audit_id: uuid!, $limit: Int!, $offset: Int!, $where: blockers_bool_exp!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      id
      created_at
      blockers(where: $where, limit: $limit, offset: $offset, order_by: {created_at: desc}) {
        id
        created_at
        content
        url_id
        equalified
        blocker_type_blockers {
          id
          blocker_type {
            id
            message
            type
            blocker_type_tags {
              blocker_tag {
                id
                tag
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
  blocker_tags(order_by: {tag: asc}) {
    id
    tag
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
        acc[url.id] = url.url;
        return acc;
    }, {} as Record<string, string>);

    const latestScan = response.audits_by_pk?.scans?.[0];
    const blockers = latestScan?.blockers || [];
    const totalCount = latestScan?.blockers_aggregate?.aggregate?.count || 0;
    const availableTags = response.blocker_tags || [];

    // Format the blockers data
    const formattedBlockers = blockers.map(blocker => {
        const tags = blocker.blocker_type_blockers.flatMap(btb => 
            btb.blocker_type.blocker_type_tags?.blocker_tag ? [btb.blocker_type.blocker_type_tags.blocker_tag] : []
        );
        
        const uniqueTags = Array.from(
            new Map(tags.map(tag => [tag.id, tag])).values()
        );

        return {
            id: blocker.id,
            created_at: blocker.created_at,
            url: urlMap[blocker.url_id] || blocker.url_id,
            url_id: blocker.url_id,
            content: blocker.content,
            equalified: blocker.equalified,
            messages: blocker.blocker_type_blockers.map(btb => 
                `${btb.blocker_type.type}: ${btb.blocker_type.message}`
            ),
            tags: uniqueTags,
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
            currentTagFilter: tagFilter,
        },
    };
}