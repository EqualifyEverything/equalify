import { useQuery } from '@tanstack/react-query';
import * as API from 'aws-amplify/api';

export interface AccessRequest {
    id: string;
    name: string | null;
    email: string;
    status: string;
    created_at: string;
}

// Pending access requests (admin only). Shared by RequestsTable and the
// navigation pip, so invalidating ["accessRequests"] after approve/deny
// updates both.
export const useAccessRequests = ({ enabled = true }: { enabled?: boolean } = {}) => {
    return useQuery({
        queryKey: ['accessRequests'],
        queryFn: async () => {
            const response = (await (
                await API.post({
                    apiName: 'auth',
                    path: '/getAccessRequests',
                }).response
            ).body.json()) as any;
            if (response?.status !== 'success') {
                throw new Error(response?.message ?? 'Failed to load access requests');
            }
            return response.requests as AccessRequest[];
        },
        enabled,
        // Keep the nav indicator reasonably fresh without hammering the API
        refetchInterval: 5 * 60 * 1000,
        refetchOnWindowFocus: true,
    });
}
