import { getAuditChart as getAuditChartAuth } from "../auth"

export const getAuditChart = async() => {
    const response = await getAuditChartAuth();
    return response;
}