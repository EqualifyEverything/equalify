import { useQuery } from '@tanstack/react-query';
import { useGlobalStore } from '../utils';
import * as API from 'aws-amplify/api';
const apiClient = API.generateClient();

export const useUser = () => {
    const { authenticated } = useGlobalStore();
    return useQuery({
        queryKey: ['user', authenticated],
        queryFn: async () => (await apiClient.graphql({
            query: `query($id: uuid!) {users_by_pk(id: $id) {id name}}`,
            variables: { id: authenticated },
        }))?.data?.users_by_pk,
    });
}