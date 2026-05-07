import type { QueryClient } from '@tanstack/react-query';
import type { TelemetryConfig, QueryMetric } from './types';

/**
 * Subscribes to the QueryCache and emits a QueryMetric for every query that
 * completes (success or error). Returns a cleanup function.
 *
 * Works by intercepting QueryCache actions directly — no changes to
 * individual useQuery() calls are needed.
 */
export function setupQueryTelemetry(
  queryClient: QueryClient,
  config: TelemetryConfig
): () => void {
  if (!config.enabled || config.reporters.length === 0) return () => {};

  const { environment, reporters, filter } = config;
  const batchSize = config.batchSize ?? 20;
  const flushInterval = config.flushInterval ?? 10_000;

  // queryHash → fetch start timestamp
  const startTimes = new Map<string, number>();
  const buffer: QueryMetric[] = [];

  const flush = () => {
    if (buffer.length === 0) return;
    const batch = buffer.splice(0, buffer.length);
    reporters.forEach((r) => r.report(batch).catch(() => {}));
  };

  const intervalId = setInterval(flush, flushInterval);
  window.addEventListener('beforeunload', flush);

  const unsubscribe = queryClient.getQueryCache().subscribe((event) => {
    if (event.type !== 'updated') return;

    // TanStack Query v5 types the action on the 'updated' branch but the
    // QueryCacheNotifyEvent union is not narrowed automatically here.
    const { query, action } = event as typeof event & {
      action: { type: string };
    };

    if (filter && !filter(query.queryKey)) return;

    const hash = query.queryHash;

    if (action.type === 'fetch') {
      startTimes.set(hash, Date.now());
      return;
    }

    if (action.type === 'success' || action.type === 'error') {
      const start = startTimes.get(hash);
      if (start === undefined) return;
      startTimes.delete(hash);

      buffer.push({
        queryName: String(query.queryKey[0] ?? 'unknown'),
        queryHash: hash,
        duration: Date.now() - start,
        status: action.type,
        timestamp: Date.now(),
        environment,
      });

      if (buffer.length >= batchSize) flush();
    }
  });

  return () => {
    unsubscribe();
    clearInterval(intervalId);
    window.removeEventListener('beforeunload', flush);
    flush();
  };
}
