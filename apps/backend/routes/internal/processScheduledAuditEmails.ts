import { db, graphqlQuery, sendEmail, isStaging } from "#src/utils";
import { DateTime } from "luxon";

// interfaces copied from /frontend/src/components/AuditEmailSubscriptionList.tsx
interface EmailSubscriptionList {
  emails: EmailSubscriptionEmail[];
}
interface EmailSubscriptionEmail {
  id: string;
  email: string;
  frequency: string; // daily|weekly|monthly
  lastSent: string; // UTC date string
}

interface graphResponse {
  email_notifications: EmailSubscriptionList;
  response: JSON;
  id: string;
  name: string;
}

//"{\"emails\":[{\"id\":\"742170ae-37a2-4c22-b732-af19029130e3\",\"email\":\"sdanie28@uic.edu\",\"frequency\":\"Weekly\",\"lastSent\":\"2025-10-17T12:53:00.180Z\"},{\"id\":\"cd0bbc97-8e1c-4e2c-b3a3-3306a864dd61\",\"email\":\"negatia@gmail.com\",\"frequency\":\"Daily\",\"lastSent\":\"2025-10-17T12:53:00.180Z\"}]}",

export const processScheduledAuditEmails = async () => {
  console.log("Started processing scheduled audit emails...");
  await db.connect();
  // fetch the email_notification fields (when email_notifications and response are not null)
  const query = {
    query: `
        query GetAudits {
    audits(where: {email_notifications: {_is_null: false}, response: {_is_null: false}}) {
      id
      name
      response
      email_notifications
    }
  }`
  };
  //console.log(JSON.stringify({ query }));
  const response = await graphqlQuery(query);
  //console.log(JSON.stringify({ response }));
  let sentCount = 0;
  let subscriptionsCount = 0;
  // extract the email_notifications column
  const emailListArray: graphResponse[] = response.audits.map((audit) => {
    return {
      id: audit.id,
      name: audit.name,
      response: audit.response,
      email_notifications: JSON.parse(
        audit.email_notifications
      ) as EmailSubscriptionList,
    };
  });
  //console.log(emailListArray);
  for (const audit of emailListArray) {
    if (
      !audit.email_notifications.emails ||
      audit.email_notifications.emails.length === 0
    ) {
      continue;
    }

    for (let index = 0; index < audit.email_notifications.emails.length; index++) {
      const email = audit.email_notifications.emails[index];
      subscriptionsCount++;
      // Ignore stale lastSent dates from before emails were enabled in prod
      const emailEnabledDate = DateTime.fromISO("2026-02-19T00:00:00.000Z");
      const rawLastSent = DateTime.fromISO(email.lastSent);
      const lastSent = rawLastSent < emailEnabledDate ? emailEnabledDate : rawLastSent;
      let intervalDays = null;
      switch (email.frequency.toLowerCase()) {
        case "daily":
          intervalDays = 1;
          break;
        case "weekly":
          intervalDays = 7;
          break;
        case "monthly":
          intervalDays = 30;
          break;
      }
      const dateLimit = lastSent.plus({ days: intervalDays });
      if (DateTime.now() > dateLimit) {
        // Update lastSent in DB immediately BEFORE sending to prevent
        // duplicate sends from concurrent runEveryMinute invocations
        audit.email_notifications.emails[index].lastSent = new Date().toISOString();
        await db.query({
          text: `UPDATE "audits" SET "email_notifications"=$1 WHERE "id"=$2`,
          values: [JSON.stringify(audit.email_notifications), audit.id],
        });

        console.log(
          `Sending email to ${email.email}. Last sent ${lastSent.toISO()}, frequency: ${email.frequency}.`
        );
        sentCount++;
        await sendEmail({
          to: email.email,
          subject: `${email.frequency} Equalify Report for Audit ${audit.name}`,
          body: `<tr>
            <td style="padding:24px 24px 8px 24px; font-size:16px; line-height:1.5; color:#334155;">
              Your ${email.frequency.toLowerCase()} report for <strong>${audit.name}</strong> is ready.
            </td>
          </tr>
          <tr>
            <td align="left" style="padding:0 24px 24px 24px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" bgcolor="#186121" style="border-radius:6px;">
                    <a href="https://app${isStaging ? '-staging' : ''}.equalify.uic.edu/audits/${audit.id}"
                       style="display:inline-block; padding:12px 24px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:#186121;">
                      View Audit
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>`,
        });
      } else {
        console.log(
          `Skipping email to ${email.email}, last sent ${email.lastSent}, frequency: ${email.frequency}.`
        );
      }
    }
  }

  await db.clean();
  console.log(
    `Finished processing ${subscriptionsCount} scheduled audit emails, ${sentCount} emails sent.`
  );
  return;
};

