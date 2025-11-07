import { getAuditTable as getAuditTableAuth } from "../auth"

export const getAuditTable = async() => {
    const response = await getAuditTableAuth();
    return response;
}