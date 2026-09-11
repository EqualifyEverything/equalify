import { parser } from "@aws-lambda-powertools/parser/middleware";
import middy from "@middy/core";

import { logger, metrics } from "./telemetry.ts";
import { scansSchema } from "../../../shared/types/scansSchema.zod.ts";
import { MetricUnit } from "@aws-lambda-powertools/metrics";

import { SQSClient, SendMessageBatchCommand } from "@aws-sdk/client-sqs";
const sqsClient = new SQSClient({ region: process.env.AWS_REGION ?? "us-east-2" });
const htmlQueueUrl =
  process.env.SQS_HTML_QUEUE_URL ?? "https://sqs.us-east-2.amazonaws.com/380610849750/scanHtml.fifo";

const pdfQueueUrl =
  process.env.SQS_PDF_QUEUE_URL ?? "https://sqs.us-east-2.amazonaws.com/380610849750/scanPdf.fifo";

const RESULTS_ENDPOINT_PROD = process.env.SCAN_WEBHOOK_URL ?? "https://api.equalifyapp.com/public/scanWebhook";
const RESULTS_ENDPOINT_STAGING = process.env.SCAN_WEBHOOK_URL_STAGING ?? "https://api-staging.equalifyapp.com/public/scanWebhook";
const getResultsEndpoint = (isStaging?: boolean) => isStaging ? RESULTS_ENDPOINT_STAGING : RESULTS_ENDPOINT_PROD;

// If a URL never makes it onto an scan-html/scan-pdf queue (SendMessageBatch partial
// failure, or the whole send throwing), nothing will ever call scanWebhook for it and
// its scan can never reach 100% - it just hangs until the 15-minute stuck-scan sweep.
// Report it as failed directly so the scan can still complete.
const reportUnsentUrlsAsFailed = async (items: { auditId: string; scanId: string; urlId: string; url: string; isStaging?: boolean }[], reason: string) => {
  await Promise.allSettled(items.map((item) =>
    fetch(getResultsEndpoint(item.isStaging), {
      method: "post",
      body: JSON.stringify({
        auditId: item.auditId,
        scanId: item.scanId,
        urlId: item.urlId,
        url: item.url,
        status: "failed",
        error: reason,
        blockers: [],
      }),
      headers: { "Content-Type": "application/json" },
    }).then((res) => {
      if (!res.ok) {
        logger.error(`Failed to report unsent URL ${item.urlId} to webhook: ${res.status}`);
      }
    }).catch((err) => {
      logger.error(`Error reporting unsent URL ${item.urlId} to webhook`, err as Error);
    })
  ));
};

export const handler = middy()
  .use(parser({ schema: scansSchema }))
  .handler(async (event): Promise<void> => {
    logger.info(`Received ${event.urls?.length || 0} URLs to route`);
    
    // Check for no URLs in request
    if(event.urls?.length ===0){
      logger.info(`No URLs received in request, exiting!`);
      return;
    }
    // get the type="html" URLs
    const htmlUrls = event.urls.filter((item) => {
      return item.type === "html";
    });
    logger.info(`Found ${htmlUrls.length} HTML URLs and ${event.urls.length - htmlUrls.length} PDF URLs`);

    // we can pass 10 events at a time to SQS
    const HtmlBatches = chunkArray(htmlUrls, 10);

    // for each batch, send to SQS
    for (const batch of HtmlBatches) {
      const formattedMessages = batch.map((item) => {
        return {
          MessageGroupId: item.auditId,
          Id: item.urlId,
          MessageDeduplicationId: `${item.scanId}-${item.urlId}`,
          MessageBody: JSON.stringify({
            data: item,
          }),
        };
      });
      const command = new SendMessageBatchCommand({
        QueueUrl: htmlQueueUrl,
        Entries: formattedMessages,
      });
      try {
        const response = await sqsClient.send(command);
        if (response.Successful && response.Successful.length > 0) {
          logger.info(`HTML Batch send successful: ${response.Successful.length} messages sent`);
        }
        if (response.Failed && response.Failed.length > 0) {
          logger.error(`HTML Messages failed to send: ${JSON.stringify(response.Failed)}`);
          const failedIds = new Set(response.Failed.map((f) => f.Id));
          const failedItems = batch.filter((item) => failedIds.has(item.urlId));
          await reportUnsentUrlsAsFailed(failedItems, "Failed to queue URL for scanning");
        }
      } catch (error) {
        logger.error("Error sending HTML batch:", error as Error);
        await reportUnsentUrlsAsFailed(batch, "Failed to queue URL for scanning");
      }
    }

    // PDF routing
    const pdfUrls = event.urls.filter((item) => {
      return item.type === "pdf";
    });

    // we can pass 10 events at a time to SQS
    const PdfBatches = chunkArray(pdfUrls, 10);
    // for each batch, send to SQS
    for (const batch of PdfBatches) {
      const formattedMessages = batch.map((item) => {
        return {
          MessageGroupId: item.auditId,
          Id: item.urlId,
          MessageDeduplicationId: `${item.scanId}-${item.urlId}`,
          MessageBody: JSON.stringify({
            data: item,
          }),
        };
      });
      const command = new SendMessageBatchCommand({
        QueueUrl: pdfQueueUrl,
        Entries: formattedMessages,
      });
      try {
        const response = await sqsClient.send(command);
        if (response.Successful && response.Successful.length > 0) {
          logger.info(`PDF Batch send successful: ${response.Successful.length} messages sent`);
        }
        if (response.Failed && response.Failed.length > 0) {
          logger.error(`PDF Messages failed to send: ${JSON.stringify(response.Failed)}`);
          const failedIds = new Set(response.Failed.map((f) => f.Id));
          const failedItems = batch.filter((item) => failedIds.has(item.urlId));
          await reportUnsentUrlsAsFailed(failedItems, "Failed to queue URL for scanning");
        }
      } catch (error) {
        logger.error("Error sending PDF batch:", error as Error);
        await reportUnsentUrlsAsFailed(batch, "Failed to queue URL for scanning");
      }
    }


    logger.info("Finished sending batch");
    metrics.addMetric("scanRequest", MetricUnit.Count, 1);
  });

function chunkArray<T>(array: T[], chunkSize: number): T[][] {
  const result: T[][] = [];
  for (let i = 0; i < array.length; i += chunkSize) {
    result.push(array.slice(i, i + chunkSize));
  }
  return result;
}
