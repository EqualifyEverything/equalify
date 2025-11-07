import { getAuditResults as getAuditResultsAuth } from "../auth"

export const getAuditResults = async() => {
    const response = await getAuditResultsAuth();
    return response;
}