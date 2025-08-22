export const slack = async (text) => {
    await fetch(process.env.SLACK_WEBHOOK, { method: 'POST', body: JSON.stringify({ text }) })
}