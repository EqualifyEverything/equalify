import { Logger } from "@aws-lambda-powertools/logger";
import { Metrics } from "@aws-lambda-powertools/metrics";

export const metrics = new Metrics({
  namespace: "equalifyuic",
  serviceName: "aws-lambda-scan-html",
});
export const logger = new Logger({ serviceName: "aws-lambda-scan-html" });