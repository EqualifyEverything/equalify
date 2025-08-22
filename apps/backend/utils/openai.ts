import { AzureOpenAI } from 'openai';
export const openai = new AzureOpenAI({
    endpoint: process.env.AZURE_OPENAI_ENDPOINT,
    apiKey: process.env.AZURE_OPENAI_APIKEY,
    apiVersion: process.env.AZURE_OPENAI_APIVERSION,
});