import { db, event, isStaging, sendEmail } from "#src/utils";

export const inviteUser = async () => {
  if (process.env.SSO_ENABLED && process.env.SSO_EMAIL_DOMAINS) {
    const ssoEmailDomains = JSON.parse(process.env.SSO_EMAIL_DOMAINS);
    if (!ssoEmailDomains.includes(event.body.email.split('@')[1])) {
      return { status: 'error', message: `Email domain not authorized for invitation.` };
    }
  }
  await db.connect();
  const inviteExists = (await db.query({
    text: `SELECT id FROM invites WHERE email=$1`,
    values: [event.body.email],
  })).rows?.[0]?.id;

  if (inviteExists) {
    await db.clean();
    return { status: 'error', message: `Invite already exists for this email address.` };
  }

  const userExists = (await db.query({
    text: `SELECT id FROM users WHERE email=$1`,
    values: [event.body.email],
  })).rows?.[0]?.id;
  
  if (userExists) {
    await db.clean();
    return { status: 'error', message: `User already exists for this email address.` };
  }

  await db.query({
    text: `INSERT INTO "invites" ("user_id", "email") VALUES ($1, $2)`,
    values: [event.claims.sub, event.body.email],
  });
  await db.clean();
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
                    <a href="https://app${isStaging ? '-staging' : ''}.equalify.uic.edu/login" 
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
  return { status: 'success' };
}