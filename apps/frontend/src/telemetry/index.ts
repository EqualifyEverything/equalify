export { setupQueryTelemetry } from './queryObserver';
export { CloudWatchReporter } from './reporters/cloudwatch';
export { ConsoleReporter } from './reporters/console';
export type {
  TelemetryConfig,
  TelemetryReporter,
  QueryMetric,
  Environment,
} from './types';
export type { CloudWatchReporterConfig } from './reporters/cloudwatch';
