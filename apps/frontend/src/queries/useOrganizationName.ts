import { useQuery } from '@tanstack/react-query';

const GRAPHQL_URL = import.meta.env.VITE_GRAPHQL_URL + "/v1/graphql";

export const ORGANIZATION_NAME_KEY = 'sso_organization_name';

// Read anonymously so it's available on the login and request access screens
const fetchOrganizationName = async () => {
    const res = await fetch(GRAPHQL_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            query: `query($key: String!) { options_by_pk(key: $key) { value } }`,
            variables: { key: ORGANIZATION_NAME_KEY },
        }),
    });
    const json = await res.json();
    return (json?.data?.options_by_pk?.value ?? '').trim() as string;
};

export const useOrganizationName = () => useQuery({
    queryKey: ['organizationName'],
    queryFn: fetchOrganizationName,
    staleTime: 5 * 60 * 1000,
});

// "your UIC account", or "your organization account" when no name is set
export const ssoAccountLabel = (organizationName?: string) =>
    `your ${organizationName?.trim() || 'organization'} account`;
