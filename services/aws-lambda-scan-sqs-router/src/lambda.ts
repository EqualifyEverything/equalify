import { parser } from "@aws-lambda-powertools/parser/middleware";
import middy from "@middy/core";

import { logger, metrics } from "./telemetry.ts";
import { scansSchema } from "../../../shared/types/scansSchema.zod.ts";
import { MetricUnit } from "@aws-lambda-powertools/metrics";

import { SQSClient, SendMessageBatchCommand } from "@aws-sdk/client-sqs";
const sqsClient = new SQSClient({ region: "us-east-2" });
const htmlQueueUrl =
  "https://sqs.us-east-2.amazonaws.com/380610849750/scanHtml.fifo";

const pdfQueueUrl =
  "https://sqs.us-east-2.amazonaws.com/380610849750/scanPdf.fifo";

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
          MessageDeduplicationId: item.urlId,
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
        }
      } catch (error) {
        logger.error("Error sending HTML batch:", error as Error);
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
          MessageDeduplicationId: item.urlId,
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
        }
      } catch (error) {
        logger.error("Error sending PDF batch:", error as Error);
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
