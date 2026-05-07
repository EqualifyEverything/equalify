import type { TelemetryReporter, QueryMetric } from '../types';

export interface CloudWatchReporterConfig {
  /**
   * URL of the backend endpoint that proxies to CloudWatch PutMetricData.
   * Expected to accept POST with a CloudWatch-shaped JSON body.
   */
  endpoint: string;
  /** CloudWatch namespace. Default: 'Equalify/Frontend' */
  namespace?: string;
  /** Extra headers sent with every request (e.g. an API key). */
  headers?: Record<string, string>;
}

interface MetricDatum {
  MetricName: string;
  Dimensions: { Name: string; Value: string }[];
  Value: number;
  Unit: 'Milliseconds';
  Timestamp: string;
}

interface PutMetricDataPayload {
  Namespace: string;
  MetricData: MetricDatum[];
}

export class CloudWatchReporter implements TelemetryReporter {
  private readonly endpoint: string;
  private readonly namespace: string;
  private readonly headers: Record<string, string>;

  constructor(config: CloudWatchReporterConfig) {
    this.endpoint = config.endpoint;
    this.namespace = config.namespace ?? 'Equalify/Frontend';
    this.headers = { 'Content-Type': 'application/json', ...config.headers };
  }

  async report(metrics: QueryMetric[]): Promise<void> {
    const payload: PutMetricDataPayload = {
      Namespace: this.namespace,
      MetricData: metrics.map((m) => ({
        MetricName: 'QueryDuration',
        Dimensions: [
          { Name: 'QueryName', Value: m.queryName },
          { Name: 'Environment', Value: m.environment },
          { Name: 'Status', Value: m.status },
        ],
        Value: m.duration,
        Unit: 'Milliseconds',
        Timestamp: new Date(m.timestamp).toISOString(),
      })),
    };

    await fetch(this.endpoint, {
      method: 'POST',
      headers: this.headers,
      body: JSON.stringify(payload),
      // keepalive allows the request to outlive the page (e.g. beforeunload flush)
      keepalive: true,
    });
  }
}
