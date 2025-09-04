import type { SQSEvent, SQSRecord } from "aws-lambda";
import middy from "@middy/core";
import {
  BatchProcessor,
  EventType,
  processPartialResponse,
} from "@aws-lambda-powertools/batch";
import { PartialItemFailureResponse } from "@aws-lambda-powertools/batch/types";
import { logMetrics } from "@aws-lambda-powertools/metrics/middleware";
import { MetricUnit } from "@aws-lambda-powertools/metrics";

import {logger, metrics} from './telemetry'
import scan from "./scan";

const processor = new BatchProcessor(EventType.SQS);

// Process a single SQS Record
const recordHandler = async (record: SQSRecord): Promise<void> => {
  metrics.captureColdStartMetric();
  const startTime = performance.now();
  const payload = record.body;
  if (payload) {
    try {
      const payloadParsed = JSON.parse(payload);
      const job = JSON.parse(payloadParsed);
      logger.info("Processing ", job);
      metrics.addMetric("scansStarted", MetricUnit.Count, 1);
      const results = await scan(job);
      logger.info(JSON.stringify(results));
      // do something with the item
    } catch (error) {
      throw error;
    } finally {
      const endTime = performance.now(); // End timing
      const executionDuration = endTime - startTime; // Calculate duration in milliseconds

      // Add a custom metric for execution duration
      metrics.addMetric(
        "ScanDuration",
        MetricUnit.Milliseconds,
        executionDuration
      );
    }
  }
  metrics.publishStoredMetrics();
};

// handle batch
const batchHandler = async (event: SQSEvent, context: any) =>
  processPartialResponse(event, recordHandler, processor, {
    context,
  });

// finally, export the handler
export const handler = middy<SQSEvent, PartialItemFailureResponse>(
  batchHandler
).use(logMetrics(metrics, { captureColdStartMetric: true }));
