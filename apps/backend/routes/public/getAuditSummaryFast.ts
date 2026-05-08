import { getAuditSummaryFast as getAuditSummaryAuth } from "../auth"

export const getAuditSummaryFast = async () => {
    const response = await getAuditSummaryAuth();
        return response;
    
}