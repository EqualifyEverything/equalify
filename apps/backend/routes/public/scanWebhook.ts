import { event } from "#src/utils"

export const scanWebhook = async() => {
    console.log(JSON.stringify(event));
    return;
} 