import { getAuditRecommendations as getAuditRecommendationsAuth } from "../auth"

export const getAuditRecommendations = async () => {
    const response = await getAuditRecommendationsAuth();
    return response;
}
