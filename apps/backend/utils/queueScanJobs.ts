import { LambdaClient, InvokeCommand } from '@aws-sdk/client-lambda';
import { isStaging } from './isStaging';

const lambda = new LambdaClient();

// Asynchronous ("Event") Lambda invokes reject payloads over 1 MB with
// RequestEntityTooLargeException. A scan's whole URL list used to go out in one
// invoke, so audits past roughly 3,500 URLs never reached the queue at all and
// sat at 0% until the stuck-scan sweep timed them out (#660). Chunk by measured
// serialized size, keeping generous headroom under the limit.
export const MAX_INVOKE_PAYLOAD_BYTES = 512 * 1024;

export interface ScanJobUrl {
    id: string;
    url: string;
    type: string;
}

export interface ScanJob {
    auditId: string;
    scanId: string;
    urlId: string;
    url: string;
    type: string;
    isStaging: boolean;
}

export const buildScanJobs = (auditId: string, scanId: string, urls: ScanJobUrl[]): ScanJob[] =>
    urls.map((u) => ({ auditId, scanId, urlId: u.id, url: u.url, type: u.type, isStaging }));

export const chunkScanJobsBySize = <T>(jobs: T[], maxBytes: number = MAX_INVOKE_PAYLOAD_BYTES): T[][] => {
    const chunks: T[][] = [];
    let current: T[] = [];
    let currentBytes = 0;
    for (const job of jobs) {
        const bytes = Buffer.byteLength(JSON.stringify(job), 'utf8') + 1; // +1 for the array separator
        if (current.length > 0 && currentBytes + bytes > maxBytes) {
            chunks.push(current);
            current = [];
            currentBytes = 0;
        }
        current.push(job);
        currentBytes += bytes;
    }
    if (current.length > 0) chunks.push(current);
    return chunks;
};

/**
 * Hands a scan's URLs to the SQS router Lambda, spread across as many async
 * invokes as needed to stay under the 1 MB payload cap. The invokes are issued
 * in order, but an 'Event' invoke returns as soon as Lambda accepts it, so the
 * router executions for different chunks can run concurrently and their pages
 * may interleave on the queue. Nothing depends on URL order.
 *
 * A failed invoke throws to the caller and the scan row is left as it is: pages
 * that never reach the queue never call scanWebhook, so the stuck-scan sweep in
 * runEveryMinute closes them out as url_timeout after its inactivity window.
 */
export const queueScanJobs = async ({ auditId, scanId, urls }: { auditId: string; scanId: string; urls: ScanJobUrl[] }) => {
    const jobs = buildScanJobs(auditId, scanId, urls);
    const chunks = chunkScanJobsBySize(jobs);
    for (const chunk of chunks) {
        await lambda.send(new InvokeCommand({
            FunctionName: process.env.SQS_ROUTER_FUNCTION_NAME ?? 'aws-lambda-scan-sqs-router',
            InvocationType: 'Event',
            Payload: JSON.stringify({ urls: chunk }),
        }));
    }
    console.log('Scan jobs queued:', { auditId, scanId, urls: jobs.length, invocations: chunks.length });
    return { urls: jobs.length, invocations: chunks.length };
};
