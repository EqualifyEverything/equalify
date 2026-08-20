-- scans.blocker_count / equalified_count are denormalized rollups written by
-- scanWebhook.ts at scan completion (and backfilled by
-- routes/internal/migrateStaleBlockers.ts) so getAuditChart/getQuickScans
-- don't need to aggregate live over blockers. Missing from the base
-- schema.sql dump for installs created before this was added; IF NOT EXISTS
-- makes this safe to run on a fresh install too, where schema.sql already
-- includes both columns.

ALTER TABLE public.scans
    ADD COLUMN IF NOT EXISTS blocker_count integer DEFAULT 0 NOT NULL,
    ADD COLUMN IF NOT EXISTS equalified_count integer DEFAULT 0 NOT NULL;
