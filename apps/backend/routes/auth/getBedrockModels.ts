import { BedrockClient, ListFoundationModelsCommand } from "@aws-sdk/client-bedrock";

const bedrockMgmt = new BedrockClient({ region: process.env.AWS_REGION || "us-east-1" });

export const getBedrockModels = async () => {
    const response = await bedrockMgmt.send(new ListFoundationModelsCommand({}));

    const models = (response.modelSummaries ?? [])
        .filter(m =>
            m.inferenceTypesSupported?.includes("ON_DEMAND") &&
            m.outputModalities?.includes("TEXT") &&
            m.modelLifecycle?.status === "ACTIVE"
        )
        .map(m => ({
            modelId: m.modelId,
            modelName: m.modelName,
            providerName: m.providerName,
        }));

    return { models };
};
