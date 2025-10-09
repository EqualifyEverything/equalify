import { db, event, graphqlQuery } from '#src/utils';

export const getAuditResults = async () => {
    const auditId = event.queryStringParameters.id;

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

    const query = {
        query: `query ($audit_id: uuid) {
  blockers(where: {audit_id: {_eq: $audit_id}}) {
    id
    created_at
    content
    targets
    url_id
    equalified
    blocker_updates {
      created_at
      equalified
    }
    blocker_type_blockers {
      id
      blocker {
        equalified
      }
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
}`,
        variables: { audit_id: auditId },
    };
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    console.log(JSON.stringify({ response }));

    // Get URL lookup map for easier reference
    const urlMap = urls.reduce((acc, url) => {
        acc[url.id] = url.url;
        return acc;
    }, {});

    const jsonRows = response.blockers.map(blocker => {
        return {
            blocker_id: blocker.id,
            url_id: urlMap[blocker.url_id],
            html: blocker.content,
            equalified: blocker.equalified,
            created_at: blocker.created_at,
            messages: blocker.blocker_type_blockers.map(obj =>
                `${obj.blocker_type.type}: ${obj.blocker_type.message}`
            )
        }
    });

    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
        body: {
            ...audit,
            blockers: jsonRows,
        },
    };
}