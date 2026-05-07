import { CloudWatchClient, PutMetricDataCommand } from '@aws-sdk/client-cloudwatch';
import { event, isStaging } from '#src/utils';

const cloudwatch = new CloudWatchClient({ region: process.env.AWS_REGION ?? 'us-east-2' });

// Separate namespaces ensure staging/production metrics never mix in CloudWatch
const NAMESPACE = isStaging ? 'Equalify/Frontend-Staging' : 'Equalify/Frontend';

// CloudWatch hard limit is 1000 datums per call; cap below the batch size used by the frontend
const MAX_DATUMS = 150;

export const putMetrics = async () => {
    const { MetricData } = event.body;

    if (!Array.isArray(MetricData) || MetricData.length === 0) {
        return { success: false, message: 'MetricData must be a non-empty array' };
    }

    const metricData = MetricData
        .slice(0, MAX_DATUMS)
        .filter((d: any) => d?.MetricName && typeof d?.Value === 'number')
        .map((d: any) => ({
            MetricName: String(d.MetricName).slice(0, 256),
            Value: d.Value,
            Unit: d.Unit ?? 'None',
            Timestamp: d.Timestamp ? new Date(d.Timestamp) : new Date(),
            Dimensions: Array.isArray(d.Dimensions)
                ? d.Dimensions.slice(0, 10).map((dim: any) => ({
                    Name: String(dim.Name ?? '').slice(0, 256),
                    Value: String(dim.Value ?? '').slice(0, 256),
                }))
                : [],
        }));

    if (metricData.length === 0) {
        return { success: false, message: 'No valid metric datums' };
    }

    try {
        await cloudwatch.send(new PutMetricDataCommand({
            Namespace: NAMESPACE,
            MetricData: metricData,
        }));
    } catch (err) {
        console.error('putMetrics: CloudWatch error', err);
        return { success: false, message: 'Failed to write metrics' };
    }

    return { success: true };
};
