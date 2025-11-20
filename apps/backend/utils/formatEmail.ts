export const formatEmail = ({ body }) => {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Equalify Audit Notification</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f3f4f6;">
    <tr>
      <td align="center" style="padding:20px 10px;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden; font-family:Helvetica, Arial, sans-serif; color:#334155;">
          
          <!-- Header -->
          <tr>
            <td align="center" style="padding:20px 24px 10px 24px; background-color:#ffffff; border-bottom:1px solid #e5e7eb;">
              <a href="https://equalify.app/"><img src="https://equalify.app/wp-content/uploads/2024/04/Equalify-Logo-768x237.png" alt="Equalify" style="max-width:160px; height:auto; display:block;" /></a>
            </td>
          </tr>

            ${body}

          <!-- Unsubscribe / Settings -->
          <tr>
            <td style="padding:0 24px 24px 24px; font-size:13px; line-height:1.4; color:#334155;">
              Don't want these emails?
              <a href="https://app-staging.equalify.uic.edu/unsubscribe" style="color:#334155; text-decoration:underline;">
                Unsubscribe
              </a>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:16px 24px 24px 24px; font-size:12px; line-height:1.4; color:#6b7280; border-top:1px solid #e5e7eb;">
              This is an automated email from Equalify. Please do not reply directly to this message.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
};
