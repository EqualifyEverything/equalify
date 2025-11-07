import { db, event, graphqlQuery } from '#src/utils';

export const getAuditResults = async () => {
    const auditId = event.queryStringParameters.id;

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

    const query = {
        query: `query ($audit_id: uuid!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      blockers {
        id
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
    }
  }
}`,
        variables: { audit_id: auditId },
    };
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    console.log(JSON.stringify({ response }));

    // Get URL lookup map for easier reference
    const urlMap = urls.reduce((acc, url) => {
        acc[url.id] = { url: url.url, type: url.type};
        return acc;
    }, {});
    
    const jsonRows = response.audits_by_pk.scans[0]?.blockers?.map(blocker => {
        return {
            blocker_id: blocker.id,
            url_id: urlMap[blocker.url_id].url,
            html: blocker.content,
            equalified: blocker.equalified,
            created_at: blocker.created_at,
            type: urlMap[blocker.url_id].type,
            messages: blocker.blocker_messages.map(obj =>
                `${obj.message.category}: ${obj.message.content}`
            )
        }
    }) ?? [];

    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
        body: {
            ...audit,
            blockers: jsonRows,
        },
    };
}