import { event, sendEmail } from "#src/utils";

export const inviteUser = async () => {
    await sendEmail({
        to: event.body.email,
        subject: `You are invited to join Equalify`,
        body: `Hello,\n\nYou are invited to join Equalify. Please visit the following link:\nhttps://app-staging.equalify.uic.edu/login\n\nThank you,\nEqualify`
    });
    return;
}