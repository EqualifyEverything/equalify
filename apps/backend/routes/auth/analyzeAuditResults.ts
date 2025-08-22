import { db, event, graphqlQuery, openai } from '#src/utils';

export const analyzeAuditResults = async () => {
    const audit_id = event.queryStringParameters.id;

    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [audit_id],
    })).rows[0];
    const urls = (await db.query({
        text: `SELECT "id", "url" FROM "urls" WHERE "audit_id" = $1`,
        values: [audit_id],
    })).rows;

    const response = await graphqlQuery({
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
    });

    const urlMap = urls.reduce((acc, url) => {
        acc[url.id] = url.url;
        return acc;
    }, {});

    const rows = response.nodes.slice(0, 10000).map(node => {
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
    const aiResponse = await openai.chat.completions.create({
        model: 'gpt-4.1-mini',
        response_format: { type: 'json_object' },
        max_completion_tokens: 32768,
        messages: [{
            role: "system",
            content: `
                Your job is to analyze these audit results and return HTML with a detailed summary, breakdown, and infographics. Use CSS for styling.
                Use Equalify branding (you can use this image: https://sales.equalify.app/equalifyv2.png).

                You must respond with this format:
                \`\`\`json
                {"result":"HTML CONTENT"}
                \`\`\`

                Here is the audit metadata:
                \`\`\`json
                ${JSON.stringify(audit)}
                \`\`\`

                Here are the audit results:
                \`\`\`json
                ${JSON.stringify(rows)}
                \`\`\`
            `,
        }
        ],
    });
    const parsedResponse = JSON.parse(aiResponse?.choices?.[0]?.message?.content)?.result;
    await db.clean();
    return {
        statusCode: 200,
        headers: { 'content-type': 'text/html' },
        body: parsedResponse,
    }
}