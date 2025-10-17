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
const RESULTS_ENDPOINT = "https://api-staging.equalifyapp.com/public/scanWebhook";

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
            const sendResultsResponse = await fetch(RESULTS_ENDPOINT, {
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
          logger.error("Error converting to EqualifyV2 format:", JSON.stringify(results));
        }
      } else {
        // Scan failed or returned no results - notify webhook of failure
        logger.error(`Job [auditId: ${job.auditId}, scanId: ${job.scanId}, urlId: ${job.urlId}] Scan failed - no results returned`);
        try {
          const failurePayload = {
            auditId: job.auditId,
            scanId: job.scanId,
            urlId: job.urlId,
            status: 'failed',
            error: 'Scan failed to produce results',
            blockers: []
          };
          
          const sendResultsResponse = await fetch(RESULTS_ENDPOINT, {
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
      }
      
    } catch (error) {
      logger.error("Scan Error!", error as string);
      throw error; // Only throw for actual scan failures
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
