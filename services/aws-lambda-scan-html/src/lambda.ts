import type { SQSEvent, SQSRecord } from 'aws-lambda'
import middy from '@middy/core';
import { captureLambdaHandler } from '@aws-lambda-powertools/tracer/middleware';
import { processPartialResponse, SqsFifoPartialProcessor } from '@aws-lambda-powertools/batch';
import { PartialItemFailureResponse } from '@aws-lambda-powertools/batch/types';

import { Tracer } from '@aws-lambda-powertools/tracer';
import { Logger } from '@aws-lambda-powertools/logger';


const logger = new Logger({ serviceName: 'aws-lambda-scan-html' });
const processor = new SqsFifoPartialProcessor();
const tracer = new Tracer({ serviceName: 'serverlessAirline' });

// Process a single SQS Record
const recordHandler = (record: SQSRecord): void => {
  const subsegment = tracer.getSegment()?.addNewSubsegment('### recordHandler'); 
  subsegment?.addAnnotation('messageId', record.messageId); 

  const payload = record.body;
  if (payload) {
    try {
      const item = JSON.parse(payload);
      logger.info("Processing ", item);
      // do something with the item
      subsegment?.addMetadata('item', item);
    } catch (error) {
      subsegment?.addError(error as Error);
      throw error;
    }
  }

  subsegment?.close(); 
};

// handle batch
const batchHandler = async (event:SQSEvent, context: any) =>
  processPartialResponse(event, recordHandler, processor, {
    context,
});

// finally, export the handler
export const handler = middy<SQSEvent, PartialItemFailureResponse>(batchHandler)
  .use(captureLambdaHandler(tracer))
