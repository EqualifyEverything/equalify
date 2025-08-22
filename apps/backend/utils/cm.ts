export const cm = {
    addSubscriber: async ({ email, name = '', isLead = true }) => {
        await fetch(`https://api.createsend.com/api/v3.3/subscribers/${isLead ? process.env.CM_LEADS : process.env.CM_CUSTOMERS}.json`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                EmailAddress: email,
                Name: name,
                ConsentToTrack: 'Yes',
                Resubscribe: true,
            })
        });
        return;
    },
    checkSubscriber: async ({ listId, email }) => {
        return (await (await fetch(`https://api.createsend.com/api/v3.3/subscribers/${listId}.json?email=${encodeURIComponent(email)}`, {
            method: 'GET',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
        })).json())?.Code !== 203;
    },
    createDraftCampaign: async ({ Name, Subject, FromName, FromEmail, ReplyTo, ListIDs, TemplateID, TemplateContent }) => {
        const campaignId = await (await fetch(`https://api.createsend.com/api/v3.3/campaigns/${process.env.CM_CLIENT_ID}/fromtemplate.json`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                Name,
                Subject,
                FromName,
                FromEmail,
                ReplyTo,
                ListIDs,
                TemplateID,
                TemplateContent,
            })
        })).json();
        console.log(campaignId);
        return campaignId;
    },
    createTemplate: async () => {
        const templateId = await (await fetch(`https://api.createsend.com/api/v3.3/templates/${process.env.CM_CLIENT_ID}.json`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                Name: "Compose Template",
                HtmlPageURL: "https://files.wallstreetbeats.com/simple.html"
            })
        })).json();
        console.log(templateId);
        return templateId;
    },
    getTemplates: async () => {
        const response = await (await fetch(`https://api.createsend.com/api/v3.3/clients/${process.env.CM_CLIENT_ID}/templates.json`, {
            method: 'GET',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
        })).json();
        console.log(JSON.stringify({ response }));
        return response;
    },
    removeSubscriber: async ({ listId, email }) => {
        return (await (await fetch(`https://api.createsend.com/api/v3.3/subscribers/${listId}.json?email=${encodeURIComponent(email)}`, {
            method: 'DELETE',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` }
        })).text());
    },
    sendCampaign: async ({ campaignId, confirmationEmail }) => {
        await fetch(`https://api.createsend.com/api/v3.3/campaigns/${campaignId}/send.json`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                ConfirmationEmail: confirmationEmail,
                SendDate: 'Immediately',
            })
        });
        return;
    },
    sendCampaignPreview: async ({ campaignId, email }) => {
        await fetch(`https://api.createsend.com/api/v3.3/campaigns/${campaignId}/sendpreview.json`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                PreviewRecipients: [email],
            })
        });
        return;
    },
    sendSmartEmail: async ({ templateId = process.env.CM_SMART_EMAIL, from = `noreply@${process.env.URL}`, to, data = {}, attachments = [] }) => {
        const response = (await (await fetch(`https://api.createsend.com/api/v3.3/transactional/smartEmail/${templateId}/send?clientID=${process.env.CM_CLIENT_ID}`, {
            method: 'POST',
            headers: { Authorization: `Basic ${Buffer.from(`${process.env.CM_API_KEY}:x`).toString('base64')}` },
            body: JSON.stringify({
                From: from,
                To: to,
                Data: data,
                ConsentToTrack: 'Yes',
                Attachments: attachments
            })
        })).json());
        console.log(response);
        return response;
    },
};