import { event } from '#src/utils';

export const tokenGeneration = async () => {
    event.response = {
        claimsOverrideDetails: {
            claimsToAddOrOverride: {
                'https://hasura.io/jwt/claims': JSON.stringify({
                    'x-hasura-allowed-roles': ['user'],
                    'x-hasura-default-role': 'user',
                    'x-hasura-user-id': event.request.userAttributes.sub,
                    'x-hasura-org-id': event.request.userAttributes.profile,
                })
            }
        }
    };
    return event;
}