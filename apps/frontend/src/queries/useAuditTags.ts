import { useQuery } from '@tanstack/react-query';
import * as API from 'aws-amplify/api';
const apiClient = API.generateClient();

export interface AuditTag {
    id: string;
    name: string;
}

// All tags are shared across the install, and there won't be many, so the
// lookahead in AuditTagInput filters this list client-side.
// Invalidate ["auditTags"] after any tag change; that prefix also covers the
// admin breakdown query (["auditTags", "admin"]).
export const useAuditTags = () => {
    return useQuery({
        queryKey: ['auditTags'],
        queryFn: async () => ((await apiClient.graphql({
            query: `{audit_tags(order_by: {name: asc}) {id name}}`,
        })) as any)?.data?.audit_tags as AuditTag[],
    });
}

// Flattens Hasura's `tag_assignments { tag { id name } }` shape
export const tagsFromAssignments = (assignments?: { tag: AuditTag }[]): AuditTag[] =>
    (assignments ?? []).map((assignment) => assignment.tag).filter(Boolean);
