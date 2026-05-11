import { db, event, graphqlQuery, bedrock } from '#src/utils';

export const getBlockerSummary = async () => {
    const { blocker_id, refresh } = event.queryStringParameters as { blocker_id: string; refresh?: string };

    await db.connect();

    try {
        const options = (await db.query({
            text: `SELECT "key", "value" FROM "options" WHERE "key" IN ('llm_enabled', 'llm_model_id')`,
            values: [],
        })).rows as { key: string; value: string }[];

        const optMap = Object.fromEntries(options.map(o => [o.key, o.value]));
        if (optMap.llm_enabled === 'false') {
            return { disabled: true };
        }
        const modelId = optMap.llm_model_id || undefined;

        if (refresh !== 'true') {
            const existing = (await db.query({
                text: `SELECT "id", "summary", "flagged" FROM "blocker_llm_summaries" WHERE "blocker_id" = $1 ORDER BY "created_at" DESC LIMIT 1`,
                values: [blocker_id],
            })).rows[0];

            if (existing?.flagged) {
                return { flagged: true };
            }

            if (existing) {
                return { id: existing.id, summary: existing.summary, cached: true };
            }
        }

        const data = await graphqlQuery({
            query: `query($id: uuid!) {
                blockers_by_pk(id: $id) {
                    content
                    url { url }
                    blocker_messages {
                        message {
                            content
                            category
                            message_tags { tag { content } }
                        }
                    }
                }
            }`,
            variables: { id: blocker_id },
        });

        const blocker = data?.blockers_by_pk;
        if (!blocker) {
            return { statusCode: 404, body: JSON.stringify({ error: 'Blocker not found' }) };
        }

        const messages = blocker.blocker_messages.map((bm: any) =>
            `Error: ${bm.message.content} (Category: ${bm.message.category}, Tags: ${bm.message.message_tags.map((t: any) => t.tag.content).join(', ')})`
        ).join('\n');

        const prompt = `You are an accessibility expert helping a web developer fix an issue detected by an automated accessibility scanner.

The issue was found on this URL: ${blocker.url.url}

Accessibility error details:
${messages}

Affected HTML:
\`\`\`html
${blocker.content}
\`\`\`

Please provide:
1. A plain-language explanation of what this accessibility issue is and why it matters to users.
2. Clear step-by-step instructions to fix it.

Be concise and practical. Target a web developer audience.`;

        const summary = await bedrock.invoke(prompt, modelId);

        const result = (await db.query({
            text: `INSERT INTO "blocker_llm_summaries" ("blocker_id", "summary")
                   VALUES ($1, $2)
                   ON CONFLICT ("blocker_id") DO UPDATE SET "summary" = $2, "flagged" = false, "updated_at" = now()
                   RETURNING "id"`,
            values: [blocker_id, summary],
        })).rows[0];

        return { id: result.id, summary, cached: false };
    } catch (err) {
        console.error('getBlockerSummary error:', err);
        return {
            statusCode: 500,
            body: JSON.stringify({ error: String(err) }),
        };
    } finally {
        await db.clean();
    }
};
