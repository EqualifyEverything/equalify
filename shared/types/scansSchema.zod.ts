
import { z } from 'zod';

export const scansSchema = z.object({
  urls: z.array(
    z.object({
      id: z.string(),
      url: z.string(),
      type: z.string(),
    })
  )
});