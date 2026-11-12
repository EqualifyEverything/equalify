// Optional label on an ignored blocker (ignored_blockers.status): why it's
// ignored. The list is fixed; keep it in step with
// apps/backend/utils/ignoreStatuses.ts and the CHECK constraint in
// db/migrations/add_ignored_blockers_status.sql.
export const IGNORE_STATUSES = [
  { value: "false_positive", label: "False positive" },
  { value: "wont_fix", label: "Won't fix" },
  { value: "third_party", label: "Third-party content" },
  { value: "needs_review", label: "Needs review" },
  { value: "fixed_pending", label: "Fixed, awaiting rescan" },
] as const;

/** "" for no status. An unknown value (newer backend) falls back to itself. */
export const ignoreStatusLabel = (status?: string | null) =>
  status ? IGNORE_STATUSES.find((s) => s.value === status)?.label ?? status : "";

/**
 * A list the server just refetched, plus rows the user acted on that the
 * refetch dropped (an item ignored under an "active" filter, say), put back
 * at the position they had. The server's row always wins when it is present,
 * so a kept row can never mask fresher data.
 */
export const mergeKeptRows = <T,>(rows: T[], kept: { item: T; index: number }[], keyOf: (row: T) => string): T[] => {
  const merged = [...rows];
  const present = new Set(rows.map(keyOf));
  [...kept]
    .filter((entry) => !present.has(keyOf(entry.item)))
    .sort((a, b) => a.index - b.index)
    .forEach((entry) => merged.splice(Math.min(entry.index, merged.length), 0, entry.item));
  return merged;
};
