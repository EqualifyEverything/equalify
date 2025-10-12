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
import convertVeraToEqualifyV2, {
  ReportData,
} from "../../../shared/convertors/VeraToEqualify2.ts";

import { logger, metrics } from "./telemetry.ts";
import scan from "./scan.ts";
//import convertToEqualifyV2 from "../../../shared/convertors/VeraToEqualify2.ts"

const processor = new BatchProcessor(EventType.SQS);
const RESULTS_ENDPOINT = "https://api.equalifyapp.com/public/scanWebhook";

// Process a single SQS Record
const recordHandler = async (record: SQSRecord): Promise<void> => {
  metrics.captureColdStartMetric();
  const startTime = performance.now();
  const payload = record.body;

  const payloadParsed = JSON.parse(payload);
  const job = JSON.parse(payloadParsed);
  logger.info("PDF to scan payload:", job);
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
      if (results) {
        logger.info(results);
        const parsedResult = JSON.parse(results);
        const equalifiedResults = convertVeraToEqualifyV2(
          parsedResult as ReportData,
          job
        );
        logger.info(`Job [${job.id}] Scan Complete!`);

        logger.info("Sending to API...", JSON.stringify(equalifiedResults));

        const sendResultsResponse = await fetch(RESULTS_ENDPOINT, {
          method: "post",
          body: JSON.stringify(equalifiedResults),
          headers: { "Content-Type": "application/json" },
        });

        logger.info(
          "PDF-scan Results sent to API!",
          JSON.stringify(sendResultsResponse.json())
        );
      } else {
        logger.error("Error:", results);
        await sendFailedStatusToResultsEndpoint(job);
      }
    } catch (error) {
      logger.error("Scan Error!", error as string);
      await sendFailedStatusToResultsEndpoint(job);
      throw error;
    }
  }
  metrics.publishStoredMetrics();
  return;
};

const sendFailedStatusToResultsEndpoint = async (job: any) => {
  const failurePayload = {
    auditId: job.auditId,
    urlId: job.urlId,
    status: "failed",
    error: "PDF Scan encountered an error.",
    blockers: [],
  };

  const sendResultsResponse = await fetch(RESULTS_ENDPOINT, {
    method: "post",
    body: JSON.stringify(failurePayload),
    headers: { "Content-Type": "application/json" },
  });
  return sendResultsResponse;  
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
