
//"{\"emails\":[{\"id\":\"742170ae-37a2-4c22-b732-af19029130e3\",\"email\":\"sdanie28@uic.edu\",\"frequency\":\"Weekly\",\"lastSent\":\"2025-10-17T12:53:00.180Z\"},{\"id\":\"cd0bbc97-8e1c-4e2c-b3a3-3306a864dd61\",\"email\":\"negatia@gmail.com\",\"frequency\":\"Daily\",\"lastSent\":\"2025-10-17T12:53:00.180Z\"}]}",
import { db, event, graphqlQuery } from '#src/utils';

// copied from /frontend/src/components/AuditEmailSubscriptionList.tsx
export interface EmailSubscriptionList {
  emails: EmailSubscriptionEmail[];
}
interface EmailSubscriptionEmail {
  id: string;
  email: string;
  frequency: string; // daily|weekly|monthly
  lastSent: string; // UTC date string
}

export const processScheduledAuditEmails = async () => {

    // fetch the email_notification fields
    const query = {
        query: `query {
            audits {
                email_notifications
            }
        }`,
        variables: { },
    };
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    console.log(JSON.stringify({ response }));
    // extract the email_notifications column
    const emailsArray = response.data.audits.map((email)=>{
        return JSON.parse(email) as EmailSubscriptionEmail;
    });
    console.log(emailsArray);

}