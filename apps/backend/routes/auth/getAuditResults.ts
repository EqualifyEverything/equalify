import { db, event, graphqlQuery } from '#src/utils';

export const getAuditResults = async () => {
    const audit_id = event.queryStringParameters.id;
    await db.connect();
    const data = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id"=$1`,
        values: [audit_id],
    })).rows?.[0];
    await db.clean();
    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
        body: data,
    };

    await db.connect();
    const urls = (await db.query({
        text: `SELECT "id", "url" FROM "urls" WHERE "audit_id" = $1`,
        values: [audit_id],
    })).rows;
    await db.clean();

    const query = {
        query: `query ($audit_id: uuid) {
            nodes(where: {audit_id: {_eq: $audit_id}}) {
                id
                created_at
                html
                targets
                url_id
                equalified
                node_updates { created_at equalified }
                message_nodes {
                    id
                    node { equalified }
                    message {
                        id
                        message
                        type
                        message_tags { tag { id tag } }
                    }
                }
            }
        }`,
        variables: { audit_id: audit_id },
    };
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    const filteredNodes = response.nodes ?? [];
    console.log(JSON.stringify({ response }));

    // Get URL lookup map for easier reference
    const urlMap = urls.reduce((acc, url) => {
        acc[url.id] = url.url;
        return acc;
    }, {});

    const jsonRows = filteredNodes.slice(0, 10000).map(node => {
        return {
            node_id: node.id,
            url_id: urlMap[node.url_id] || '',
            html: node.html,
            targrets: node.targets,
            equalified: node.equalified,
            created_at: node.created_at,
            messages: node.message_nodes.map(mn =>
                `${mn.message.type}: ${mn.message.message}`
            )
        }
    });

    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
        body: jsonRows,
    };
}