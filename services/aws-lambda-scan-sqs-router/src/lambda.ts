import { parser } from '@aws-lambda-powertools/parser/middleware';
import middy from '@middy/core';

import { logger, metrics } from "./telemetry.ts";
import { scansSchema } from '../../../shared/types/scansSchema.zod.ts'
import { MetricUnit } from "@aws-lambda-powertools/metrics"; 

export const handler = middy()
  .use(parser({ schema: scansSchema }))
  .handler(async (event): Promise<void> => {
    for (const url of event.urls) {
      logger.info('Processing item', { url });
      metrics.addMetric("scanRequest", MetricUnit.Count, 1);
    }
  });
