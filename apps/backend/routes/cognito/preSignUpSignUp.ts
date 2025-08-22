import { event } from '#src/utils';

export const preSignUpSignUp = async () => {
    event.response.autoConfirmUser = true;
    event.response.autoVerifyEmail = true;
    return event;
}