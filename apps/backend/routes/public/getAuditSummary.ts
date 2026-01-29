import { getAuditSummary as getAuditSummaryAuth } from "../auth"

export const getAuditSummary = async () => {
    const response = await getAuditSummaryAuth();
        return response;
    
}