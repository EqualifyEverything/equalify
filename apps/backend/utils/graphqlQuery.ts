import { event, isStaging } from '.';

export const graphqlQuery = async ({ query, variables = {} }) => {
    const authorization = event?.headers?.authorization;
    const role = event?.headers?.['x-hasura-role'];
    const headers = {
        'Content-Type': 'application/json',
        ...authorization ? { authorization } : { 'x-hasura-admin-secret': process.env.DB_PASSWORD },
        ...role && ({ 'x-hasura-role': role }),
    };
    // console.log(JSON.stringify({ query, variables, headers }))
    const res = await fetch(`https://graphql${isStaging ? '-staging' : ''}.equalifyapp.com/v1/graphql`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ query, variables }),
    });
    const contentType = res.headers.get('content-type') || '';
    if (!res.ok || !contentType.includes('application/json')) {
        const text = await res.text();
        console.log(JSON.stringify({ graphqlError: { status: res.status, body: text.slice(0, 500) } }));
        throw new Error(`GraphQL endpoint returned HTTP ${res.status}`);
    }
    const response = await res.json();
    if (!response?.data) {
        console.log(JSON.stringify({ graphqlError: response }));
    }
    return response?.data;
}