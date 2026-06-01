import { BedrockRuntimeClient, ConverseCommand } from "@aws-sdk/client-bedrock-runtime";

const DEFAULT_MODEL_ID = "amazon.nova-lite-v1:0";
const bedrockClient = new BedrockRuntimeClient({ region: process.env.AWS_REGION || "us-east-2" });

export const bedrock = {
    invoke: async (prompt: string, modelId?: string): Promise<string> => {
        const command = new ConverseCommand({
            modelId: modelId || DEFAULT_MODEL_ID,
            messages: [{ role: "user", content: [{ text: prompt }] }],
            inferenceConfig: { maxTokens: 1024 },
        });
        const response = await bedrockClient.send(command);
        return response.output?.message?.content?.[0]?.text ?? '';
    },
};
