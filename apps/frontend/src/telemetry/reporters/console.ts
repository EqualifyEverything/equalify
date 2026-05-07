import type { TelemetryReporter, QueryMetric } from '../types';

export class ConsoleReporter implements TelemetryReporter {
  async report(metrics: QueryMetric[]): Promise<void> {
    metrics.forEach((m) =>
      console.debug(
        `[telemetry] ${m.environment} | ${m.queryName} → ${m.status} in ${m.duration}ms`,
        m
      )
    );
  }
}
