import { createClient } from 'graphql-ws';
import { useEffect, useState } from 'react';
import * as Auth from 'aws-amplify/auth'

const wsClient = createClient({
    url: `${import.meta.env.VITE_GRAPHQL_WSS}/v1/graphql`,
    connectionParams: async () => ({ headers: { Authorization: `Bearer ${(await Auth.fetchAuthSession()).tokens?.idToken?.toString()}` } })
});

export const useSubscription = ({ query, variables = {} }) => {
    const [data, setData] = useState(null);

    useEffect(() => {
        const unsubscribe = wsClient.subscribe({
            query,
            variables
        }, {
            next: ({ data }) => setData(data),
            error: console.error,
            complete: () => console.log('done')
        });

        return unsubscribe;
    }, [query, JSON.stringify(variables)]);

    return data;
};