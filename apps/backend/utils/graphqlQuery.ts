import { event, isStaging } from '.';

export const graphqlQuery = async ({ query, variables = {} }) => {
    const authorization = event?.headers?.authorization;
    const organizationId = event?.headers['x-hasura-org-id'];
    const role = event?.headers['x-hasura-role'];
    const headers = {
        'Content-Type': 'application/json',
        ...authorization ? { authorization } : { 'x-hasura-admin-secret': process.env.DB_PASSWORD },
        ...organizationId && ({ 'x-hasura-org-id': organizationId }),
        ...role && ({ 'x-hasura-role': role }),
    };
    // console.log(JSON.stringify({ query, variables, headers }))
    const response = (await (await fetch(`https://graphqlv2.equalify.${isStaging ? 'dev' : 'app'}/v1/graphql`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ query, variables }),
    })).json());
    if (!response?.data) {
        console.log(JSON.stringify({ graphqlError: response }));
    }
    return response?.data;
}