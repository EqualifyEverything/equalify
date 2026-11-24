// Optional label on an ignored blocker (ignored_blockers.status). The list is
// fixed; keep it in step with apps/frontend/src/utils/ignoreStatuses.ts and
// the CHECK constraint in db/migrations/add_ignored_blockers_status.sql.
export const IGNORE_STATUS_LABELS: Record<string, string> = {
    false_positive: 'False positive',
    wont_fix: "Won't fix",
    third_party: 'Third-party content',
    needs_review: 'Needs review',
    fixed_pending: 'Fixed, awaiting rescan',
};

// '' for no status. A value this build doesn't know falls back to itself,
// as in the frontend, so it is never silently blank.
export const ignoreStatusLabel = (status?: string | null) => (status ? IGNORE_STATUS_LABELS[status] ?? status : '');
