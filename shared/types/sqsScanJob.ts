
export type SqsScanJob = {
  auditId: string;
  scanId: string;
  urlId: string;
  url: string;
  type: string;
  isStaging?: boolean;
};