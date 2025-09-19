import { graphqlQuery } from '#src/utils';

export const runEveryMinute = async () => {
    // Perform health check
    const response = await graphqlQuery({ query: `{users(limit:1){id}}` });
    if (!response?.users?.[0]?.id) {
        await fetch(process.env.SLACK_WEBHOOK, {
            method: 'POST',
            body: JSON.stringify({
                text: `*Equalify UIC* - Database connection failure detected`
            })
        })
    }
    return;
}