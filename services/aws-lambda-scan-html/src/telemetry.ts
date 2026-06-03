import { Logger } from "@aws-lambda-powertools/logger";
import { Metrics } from "@aws-lambda-powertools/metrics";

export const metrics = new Metrics({
  namespace: process.env.POWERTOOLS_METRICS_NAMESPACE ?? "equalifyuic",
  serviceName: "aws-lambda-scan-html",
});
export const logger = new Logger({ serviceName: "aws-lambda-scan-html" });