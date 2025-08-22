import { db, event, graphqlQuery, openai } from '#src/utils';

export const chatWithAudit = async () => {
    const { audit_id, message, clear } = event.body;

    await db.connect();
    if (clear) {
        await db.query({
            text: `DELETE FROM "chat" WHERE "user_id"=$1 AND "audit_id"=$2`,
            values: [event.claims.sub, audit_id],
        })
        await db.clean();
        return { status: 'success', message: `Chat successfully cleared` }
    }

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

    await db.query({
        text: `INSERT INTO "chat" ("user_id", "audit_id", "role", "content") VALUES ($1, $2, $3, $4)`,
        values: [event.claims.sub, audit_id, 'user', message],
    });
    const oldMessages = (await db.query({
        text: `SELECT "role", "content" FROM "chat" WHERE "user_id"=$1 AND "audit_id" = $2 ORDER BY "created_at" ASC`,
        values: [event.claims.sub, audit_id],
    })).rows;

    const aiResponse = await openai.chat.completions.create({
        model: 'gpt-4.1-nano',
        max_completion_tokens: 32768,
        messages: [{
            role: "system",
            content: `
                Your job is to help a user understand and fix issues recognized by their Equalify accessibility audit.

                Here is the audit metadata:
                \`\`\`json
                ${JSON.stringify(audit)}
                \`\`\`
                Here are the audit scan results:
                \`\`\`json
                ${JSON.stringify(rows)}
                \`\`\`
            `,
        },
        ...oldMessages
        ],
    });
    const content = aiResponse?.choices?.[0]?.message?.content;

    await db.query({
        text: `INSERT INTO "chat" ("user_id", "audit_id", "role", "content") VALUES ($1, $2, $3, $4)`,
        values: [event.claims.sub, audit_id, 'assistant', content],
    });
    const newMessages = (await db.query({
        text: `SELECT "created_at", "role", "content" FROM "chat" WHERE "user_id"=$1 AND "audit_id" = $2 ORDER BY "created_at" DESC`,
        values: [event.claims.sub, audit_id],
    })).rows;

    await db.clean();
    return { messages: newMessages }
}