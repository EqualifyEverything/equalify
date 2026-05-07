export type Environment = 'production' | 'staging' | 'development';

export interface QueryMetric {
  queryName: string;
  queryHash: string;
  duration: number;
  status: 'success' | 'error';
  timestamp: number;
  environment: Environment;
}

export interface TelemetryReporter {
  report(metrics: QueryMetric[]): Promise<void>;
}

export interface TelemetryConfig {
  enabled: boolean;
  environment: Environment;
  reporters: TelemetryReporter[];
  /**
   * Return false to exclude a query from telemetry.
   * Receives the raw queryKey array (e.g. ['user', userId]).
   */
  filter?: (queryKey: readonly unknown[]) => boolean;
  /** Flush after this many buffered metrics. Default: 20 */
  batchSize?: number;
  /** Flush every N milliseconds. Default: 10 000 */
  flushInterval?: number;
}
