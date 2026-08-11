-- blockers.url_text is a denormalized snapshot of the URL string, written by
-- scanWebhook.ts at blocker-insert time so the URL stays visible even after
-- the urls row is later deleted (CSV change, manual removal) — see
-- getAuditTable.ts's fallback chain: live join -> url_text -> "Unknown URL".
-- Missing from the base schema.sql dump for installs created before this was
-- added; IF NOT EXISTS makes this safe to run on a fresh install too, where
-- schema.sql already includes the column.

ALTER TABLE public.blockers
    ADD COLUMN IF NOT EXISTS url_text text;
