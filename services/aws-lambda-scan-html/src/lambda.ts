import type { SQSEvent, SQSRecord } from "aws-lambda";
import middy from "@middy/core";
import {
  BatchProcessor,
  EventType,
  processPartialResponse,
} from "@aws-lambda-powertools/batch";
import type { PartialItemFailureResponse } from "@aws-lambda-powertools/batch/types";
import { logMetrics } from "@aws-lambda-powertools/metrics/middleware";
import { MetricUnit } from "@aws-lambda-powertools/metrics";

import { logger, metrics } from "./telemetry.ts";
import scan from "./scan.ts";

const processor = new BatchProcessor(EventType.SQS);

// Process a single SQS Record
const recordHandler = async (record: SQSRecord): Promise<void> => {
  metrics.captureColdStartMetric();
  const startTime = performance.now();
  const payload = record.body;

  const payloadParsed = JSON.parse(payload);
  const job = JSON.parse(payloadParsed);
  if (payload) {
    try {
      metrics.addMetric("scansStarted", MetricUnit.Count, 1);
      const results = await scan(job).then((result) => {
        const endTime = performance.now();
        const executionDuration = endTime - startTime;
        metrics.addMetric(
          "ScanDuration",
          MetricUnit.Milliseconds,
          executionDuration
        );
        return result;
      });
      if(results){
        logger.info(`Job [${job.id}] Scan Complete!`);
        logger.info(JSON.stringify(results));
      }
      
    } catch (error) {
      logger.error("Scan Error!", error as string);
      throw error;
    }
  }
  metrics.publishStoredMetrics();
  return;
};

// handle batch
const batchHandler = async (event: SQSEvent, context: any) =>
  processPartialResponse(event, recordHandler, processor, {
    context,
    throwOnFullBatchFailure: false,
    processInParallel: false,
  });

// finally, export the handler
export const handler = middy<SQSEvent, PartialItemFailureResponse>(
  batchHandler
).use(logMetrics(metrics, { captureColdStartMetric: true }));
