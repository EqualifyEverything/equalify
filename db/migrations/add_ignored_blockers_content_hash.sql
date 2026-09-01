-- ignored_blockers.content_hash_id: denormalized copy of the ignored
-- blocker's content hash, so scanWebhook can carry ignores forward to future
-- scans (and the UI can ignore/un-ignore hash-wide) without joining blockers
-- — which breaks once migrateStaleBlockers moves old rows to stale_blockers.
--
-- Production likely already has this column (scanWebhook has been writing it;
-- migrateStaleBlockers phase "ignored_hashes" backfills it) — this migration
-- exists so environments provisioned from db/schema.sql match, hence
-- IF NOT EXISTS throughout.
--
-- After running, ALSO update Hasura metadata (console or CLI):
--   1. Reload/track the new column on ignored_blockers.
--   2. Add content_hash_id to the "user" role's insert AND select permission
--      columns (insert: the ignore toggle writes it; select: the un-ignore
--      delete and carry-forward reads filter on it).

ALTER TABLE public.ignored_blockers
    ADD COLUMN IF NOT EXISTS content_hash_id uuid;

-- scanWebhook reads all hashes per audit; the UI deletes by (audit, hash).
CREATE INDEX IF NOT EXISTS ignored_blockers_audit_content_hash_idx
    ON public.ignored_blockers (audit_id, content_hash_id);

-- Backfill rows created before the column existed (same as the
-- migrateStaleBlockers "ignored_hashes" phase; safe to re-run, NULL-only).
UPDATE public.ignored_blockers ib
SET content_hash_id = b.content_hash_id
FROM public.blockers b
WHERE ib.blocker_id = b.id
  AND ib.content_hash_id IS NULL;
