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
import convertToEqualifyV2 from "../../../shared/convertors/AxeToEqualify2.ts"

const processor = new BatchProcessor(EventType.SQS);
const RESULTS_ENDPOINT_PROD = "https://api.equalifyapp.com/public/scanWebhook";
const RESULTS_ENDPOINT_STAGING = "https://api-staging.equalifyapp.com/public/scanWebhook";
const getResultsEndpoint = (isStaging?: boolean) => isStaging ? RESULTS_ENDPOINT_STAGING : RESULTS_ENDPOINT_PROD;

const sendFailedStatusToResultsEndpoint = async (job: any, errorMessage?: string) => {
  const failurePayload = {
    auditId: job.auditId,
    scanId: job.scanId,
    urlId: job.urlId,
    url: job.url,
    status: 'failed',
    error: errorMessage || 'Scan failed to produce results',
    blockers: []
  };

  try {
    const sendResultsResponse = await fetch(getResultsEndpoint(job.isStaging), {
      method: 'post',
      body: JSON.stringify(failurePayload),
      headers: {'Content-Type': 'application/json'}
    });

    if (!sendResultsResponse.ok) {
      logger.error(`Failed to send failure notification to webhook`);
    }
  } catch (webhookError) {
    logger.error("Failed to send failure notification", webhookError as Error);
  }
};

// Process a single SQS Record
const recordHandler = async (record: SQSRecord): Promise<void> => {
  metrics.captureColdStartMetric();
  const startTime = performance.now();
  const payload = record.body;

  const payloadParsed = JSON.parse(payload);
  const job = payloadParsed.data;
  
  logger.info(`Processing job: ${JSON.stringify(job)}`);
  if (payload) {
    try {
      metrics.addMetric("scansStarted", MetricUnit.Count, 1);
      
      // Wrap scan in timeout to prevent Lambda from hanging
      const SCAN_TIMEOUT = 2*60*1000; // 2 minutes max for entire scan process
      const scanPromise = scan(job).then((result) => {
        const endTime = performance.now();
        const executionDuration = endTime - startTime;
        metrics.addMetric(
          "ScanDuration",
          MetricUnit.Milliseconds,
          executionDuration
        );
        return result;
      });
      
      const timeoutPromise = new Promise<typeof scanPromise>((_, reject) => 
        setTimeout(() => reject(new Error(`Scan timeout after ${SCAN_TIMEOUT}ms`)), SCAN_TIMEOUT)
      );
      
      const results = await Promise.race([scanPromise, timeoutPromise]);
      
      if(results){
        logger.info(`Job [auditId: ${job.auditId}, scanId: ${job.scanId}, urlId: ${job.urlId}] Scan Complete!`);
        if(results.axeresults){
          const convertedResults = convertToEqualifyV2(results.axeresults, job);
        
          // shim the results payload object with status when we have results
          convertedResults.status = results.status;  

          logger.info("Converted results:", JSON.stringify(convertedResults));

          try {
            const sendResultsResponse = await fetch(getResultsEndpoint(job.isStaging), {
              method: 'post',
              body: JSON.stringify(convertedResults),
              headers: {'Content-Type': 'application/json'}
            });
            
            // FIX: Properly await the json() promise
            const responseData = await sendResultsResponse.json() as any;
            
            if (!sendResultsResponse.ok) {
              // Log but don't throw - let the message be deleted if scan succeeded
              logger.error(`Webhook failed with status ${sendResultsResponse.status}`, responseData);
            } else {
              logger.info("HTML-scan Results sent to API!", responseData);
            }
          } catch (webhookError) {
            // Log webhook errors but don't fail the entire message
            logger.error("Failed to send results to webhook", webhookError as Error);
            // Decide: throw here if you want to retry, or continue to mark as processed
          }
        } else {
          // Axe analysis failed but scan returned - send failure webhook so URL is counted as processed
          logger.error("Scan returned no axe results:", JSON.stringify(results));
          await sendFailedStatusToResultsEndpoint(job, results?.message || 'Scan completed but produced no accessibility results');
        }
      } else {
        // Scan failed or returned no results - notify webhook of failure
        logger.error(`Job [auditId: ${job.auditId}, scanId: ${job.scanId}, urlId: ${job.urlId}] Scan failed - no results returned`);
        await sendFailedStatusToResultsEndpoint(job);
      }
      
    } catch (error) {
      logger.error("Scan Error!", error as string);
      // Send failure webhook so the URL is counted as processed and doesn't block the FIFO queue
      await sendFailedStatusToResultsEndpoint(job, `Scan error: ${error instanceof Error ? error.message : String(error)}`);
      throw error;
    }
  }
  metrics.publishStoredMetrics();
  return; // Success - message will be deleted
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
