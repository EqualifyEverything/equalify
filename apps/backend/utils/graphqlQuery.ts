import { event, isStaging } from '.';

const GRAPHQL_TIMEOUT_MS = 25_000;
const RETRY_DELAYS_MS = [1_000, 2_000];

export const graphqlQuery = async ({ query, variables = {} }) => {
    const authorization = event?.headers?.authorization;
    const role = event?.headers?.['x-hasura-role'];
    const headers = {
        'Content-Type': 'application/json',
        ...authorization ? { authorization } : { 'x-hasura-admin-secret': process.env.DB_PASSWORD },
        ...role && ({ 'x-hasura-role': role }),
    };
    const url = `https://graphql${isStaging ? '-staging' : ''}.equalifyapp.com/v1/graphql`;
    const body = JSON.stringify({ query, variables });

    let lastError: Error;
    for (let attempt = 0; attempt <= RETRY_DELAYS_MS.length; attempt++) {
        if (attempt > 0) {
            await new Promise((resolve) => setTimeout(resolve, RETRY_DELAYS_MS[attempt - 1]));
        }
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), GRAPHQL_TIMEOUT_MS);
        try {
            const res = await fetch(url, { method: 'POST', headers, body, signal: controller.signal });
            clearTimeout(timer);
            const contentType = res.headers.get('content-type') || '';
            if (!res.ok || !contentType.includes('application/json')) {
                const text = await res.text();
                console.log(JSON.stringify({ graphqlError: { status: res.status, body: text.slice(0, 500), attempt } }));
                // Retry on 5xx (transient server/gateway errors); surface 4xx immediately
                if (res.status >= 500 && attempt < RETRY_DELAYS_MS.length) {
                    lastError = new Error(`GraphQL endpoint returned HTTP ${res.status}`);
                    continue;
                }
                throw new Error(`GraphQL endpoint returned HTTP ${res.status}`);
            }
            const response = await res.json();
            if (!response?.data) {
                console.log(JSON.stringify({ graphqlError: response }));
            }
            return response?.data;
        } catch (err) {
            clearTimeout(timer);
            const isRetryable = err.name === 'AbortError' || err.message?.includes('fetch failed');
            console.log(JSON.stringify({ graphqlError: { message: err.message, attempt } }));
            if (isRetryable && attempt < RETRY_DELAYS_MS.length) {
                lastError = err;
                continue;
            }
            throw err;
        }
    }
    throw lastError!;
}