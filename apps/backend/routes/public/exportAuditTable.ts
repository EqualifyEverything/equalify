import { exportAuditTable as exportAuditTableAuth } from "../auth";

export const exportAuditTable = async () => {
  return await exportAuditTableAuth();
};
