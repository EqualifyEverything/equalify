import { db, event, isStaging, sendEmail } from "#src/utils";

//
// Admin-only: approve or deny an access request. Approving creates an invite,
// which is what authorizes the email's SSO sign-in (see ensureSsoUser).
//

export const reviewAccessRequest = async () => {
    const { id, action } = event.body;
    if (!['approve', 'deny'].includes(action)) {
        return { status: 'error', message: 'Invalid action.' };
    }

    await db.connect();
    const requesterType = (await db.query({
        text: `SELECT type FROM users WHERE id=$1`,
        values: [event.claims.sub],
    })).rows?.[0]?.type;
    if (requesterType !== 'admin') {
        await db.clean();
        return { status: 'error', message: 'Admin access required.' };
    }

    const request = (await db.query({
        text: `SELECT "id", "name", "email", "status" FROM "access_requests" WHERE "id"=$1`,
        values: [id],
    })).rows?.[0];
    if (!request) {
        await db.clean();
        return { status: 'error', message: 'Access request not found.' };
    }
    if (request.status !== 'pending') {
        await db.clean();
        return { status: 'error', message: 'This request has already been reviewed.' };
    }

    if (action === 'deny') {
        await db.query({
            text: `UPDATE "access_requests" SET "status"='denied', "reviewed_by"=$2, "reviewed_at"=now(), "updated_at"=now() WHERE "id"=$1`,
            values: [id, event.claims.sub],
        });
        await db.clean();
        return { status: 'success', message: 'Request denied.' };
    }

    // approve: create the invite unless one already exists for this email
    const inviteExists = (await db.query({
        text: `SELECT id FROM invites WHERE lower(email)=lower($1)`,
        values: [request.email],
    })).rows?.[0]?.id;
    if (!inviteExists) {
        await db.query({
            text: `INSERT INTO "invites" ("user_id", "email", "name") VALUES ($1, $2, $3)`,
            values: [event.claims.sub, request.email, request.name],
        });
    }
    await db.query({
        text: `UPDATE "access_requests" SET "status"='approved', "reviewed_by"=$2, "reviewed_at"=now(), "updated_at"=now() WHERE "id"=$1`,
        values: [id, event.claims.sub],
    });
    await db.clean();

    // notify the requester; the approval already succeeded, so an email failure
    // shouldn't surface as an error to the reviewing admin
    try {
        await sendEmail({
        to: request.email,
        subject: `Your Equalify access request was approved`,
        body: `<tr>
            <td style="padding:24px 24px 8px 24px; font-size:16px; line-height:1.5; color:#334155;">
              Hello,
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 24px 24px; font-size:16px; line-height:1.5; color:#334155;">
              Your request to access Equalify has been approved. Sign in with your SSO account below to get started:
            </td>
          </tr>

          <!-- Button -->
          <tr>
            <td align="left" style="padding:0 24px 24px 24px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" bgcolor="#186121" style="border-radius:6px;">
                    <a href="${process.env.APP_URL ?? `https://app${isStaging ? '-staging' : ''}.equalify.uic.edu`}/login"
                       style="display:inline-block; padding:12px 24px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:#186121;">
                      Sign In
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>`
        });
    } catch (emailError) {
        console.error('Approval email failed to send:', request.email, emailError);
    }

    return { status: 'success', message: 'Request approved — invite created.' };
};
