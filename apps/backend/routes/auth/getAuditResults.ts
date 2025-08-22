import { db, event, graphqlQuery } from '#src/utils';

export const getAuditResults = async () => {
    const audit_id = event.queryStringParameters.id;
    const type = event.queryStringParameters.type ?? 'json';

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

    if (type === 'csv') {
        // Create CSV header
        const csvHeader = [
            'Node ID',
            'URL',
            'HTML',
            'Targets',
            'Status',
            'Created At',
            'Messages'
        ].join(',');

        const escapeField = (field) => {
            if (field === null || field === undefined) return '';
            const stringField = String(field);
            if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
                return `"${stringField.replace(/"/g, '""')}"`;
            }
            return stringField;
        };

        // Create CSV rows
        const csvRows = filteredNodes.slice(0, 10000).map(node => {
            const nodeMessages = node.message_nodes.map(mn =>
                `${escapeField(mn.message.type)}: ${escapeField(mn.message.message)}`
            ).join(' | ');

            return [
                escapeField(node.id),
                escapeField(urlMap[node.url_id] || ''),
                escapeField(node.html),
                escapeField(Array.isArray(node.targets) ? node.targets.join(', ') : node.targets),
                escapeField(node.equalified ? 'Equalified' : 'Active'),
                escapeField(node.created_at),
                escapeField(nodeMessages)
            ].join(',');
        });

        // Combine header and rows
        const csvContent = [csvHeader, ...csvRows].join('\r\n');
        return {
            statusCode: 200,
            headers: {
                'content-type': 'text/csv',
                'content-disposition': `attachment; filename="report.csv"`,
            },
            body: csvContent,
        };
    }
    else if (type === 'json') {
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
}