-- Indexes for the Detailed view's scan-history picker
-- (apps/backend/utils/getHistoricalBlockers.ts). It reads a past scan's rows
-- from stale_blockers by scan_id and their messages from
-- stale_blocker_messages by blocker_id. Without these indexes, both reads
-- scan the whole table (runEveryDay keeps adding every superseded scan to it).
--
-- Check first (`\d stale_blockers`, `\d stale_blocker_messages`): if the
-- tables already have an equivalent index under a different name, skip this.
--
-- CONCURRENTLY avoids locking the tables while runEveryDay writes to them, but
-- it can't run inside a transaction, so the Hasura SQL console (which wraps
-- every statement in one) won't work. Use psql, or run each statement on its own.

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_stale_blockers_scan_id
    ON public.stale_blockers USING btree (scan_id);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_stale_blocker_messages_blocker_id
    ON public.stale_blocker_messages USING btree (blocker_id);
