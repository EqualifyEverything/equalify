import { event, sendEmail } from "#src/utils";

export const inviteUser = async () => {
    await sendEmail({
        to: event.body.email,
        subject: `You are invited to join Equalify`,
        body: `<tr>
            <td style="padding:24px 24px 8px 24px; font-size:16px; line-height:1.5; color:#334155;">
              Hello,
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 24px 24px; font-size:16px; line-height:1.5; color:#334155;">
              ${event.claims.name} invited to join Equalify. Please accept your invite below:
            </td>
          </tr>

          <!-- Button -->
          <tr>
            <td align="left" style="padding:0 24px 24px 24px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" bgcolor="#186121" style="border-radius:6px;">
                    <a href="https://app-staging.equalify.uic.edu/login" 
                       style="display:inline-block; padding:12px 24px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:#186121;">
                      Accept Invite
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </p>`
    });
    return;
}