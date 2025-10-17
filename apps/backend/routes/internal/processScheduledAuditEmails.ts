import { db, event, graphqlQuery } from "#src/utils";
import { DateTime } from "luxon";
import { SendEmailCommand } from "@aws-sdk/client-ses";
import { SESClient } from "@aws-sdk/client-ses";

const REGION = "us-east-2";
const sesClient = new SESClient({ region: REGION });
const EMAIL_NOTIFICATION_FROM_ADDRESS = "support@equalifyapp.com"

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
  // fetch the email_notification fields (when email_notifications and response are not null)
  const query = {
    query: `
        {
            audits(where: {email_notifications: {_is_null: false}, response: {_is_null: false}}) {
                email_notifications
                response
                id
                name
            }
        }`,
    variables: {},
  };
  console.log(JSON.stringify({ query }));
  const response = await graphqlQuery(query);
  console.log(JSON.stringify({ response }));
  // extract the email_notifications column
  const emailListArray: graphResponse[] = response.data.audits.map((email) => {
    return {
      email_notifications: JSON.parse(
        email.email_notifications
      ) as EmailSubscriptionList,
      reponse: email.reponse,
    };
  });
  console.log(emailListArray);
  const currentTime = new Date();
  emailListArray.forEach(async (audit) => {
    if (
      audit.email_notifications.emails &&
      audit.email_notifications.emails.length > 0
    ) {
      let newEmailNotificationField: EmailSubscriptionList =
        audit.email_notifications;
      audit.email_notifications.emails.forEach((email, index) => {
        const lastSent = DateTime.fromISO(email.lastSent);
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
        }
        const dateLimit = lastSent.plus({ days: intervalDays });
        if (DateTime.now() > dateLimit) {
          // time to send email!
          console.log(
            `Sending email to ${email.email}. Last sent ${email.lastSent}, frequency: ${email.frequency}.`
          );
          sendEmail(email.email, JSON.stringify(audit.response), `${email.frequency} Equalify Report for Audit ${audit.name}`)
          newEmailNotificationField.emails[index].lastSent =
            new Date().toISOString();
        }
      });
      // check if we need to update the email_notifications field in the database
      if (
        JSON.stringify(newEmailNotificationField) !=
        JSON.stringify(audit.email_notifications)
      ) {
        console.log("Updating lastSent in database...", newEmailNotificationField);
        // update audit in database with new email_notification field
        await db.query({
            text: `UPDATE "audits" SET "email_notifications"=$1 WHERE "id"=$2`,
            values: [JSON.stringify(newEmailNotificationField), audit.id]
        });
      }
    }
  });
  await db.clean();
  console.log("Finished processing scheduled audit emails.")
  return;
};

async function sendEmail(address: string, content: string, subjectLine:string) {
  const sendEmailCommand = createSendEmailCommand(
    address,
    EMAIL_NOTIFICATION_FROM_ADDRESS,
    content,
    subjectLine
  );

  try {
    //return await sesClient.send(sendEmailCommand);
    console.log("Email data to be sent:", address, EMAIL_NOTIFICATION_FROM_ADDRESS, content, subjectLine);
  } catch (caught) {
    if (caught instanceof Error && caught.name === "MessageRejected") {
      /** @type { import('@aws-sdk/client-ses').MessageRejected} */
      const messageRejectedError = caught;
      console.error("Email rejected:", messageRejectedError)
      return messageRejectedError;
    }
    //throw caught;
    console.error("Error sending email!", caught);
  }
}


const createSendEmailCommand = (toAddress, fromAddress, content, subjectLine) => {
  return new SendEmailCommand({
    Destination: {
      CcAddresses: [],
      ToAddresses: [
        toAddress,
    ],
    },
    Message: {
      Body: {
        Html: {
          Charset: "UTF-8",
          Data: content,
        },
        Text: {
          Charset: "UTF-8",
          Data: content,
        },
      },
      Subject: {
        Charset: "UTF-8",
        Data: subjectLine,
      },
    },
    Source: fromAddress,
    ReplyToAddresses: [],
  });
};



