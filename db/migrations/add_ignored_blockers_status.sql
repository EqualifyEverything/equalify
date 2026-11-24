-- ignored_blockers.status: optional label saying why a blocker is ignored
-- (false positive, won't fix, ...). NULL = ignored with no status, which is
-- what every existing row keeps. A row's existence still means "ignored", so
-- counts, charts and KPIs are unchanged.
--
-- The list is fixed; keep it in step with
--   apps/frontend/src/utils/ignoreStatuses.ts
--   apps/backend/utils/ignoreStatuses.ts
-- Adding a status later = drop and re-add the CHECK below with the new value.
--
-- After running this, in Hasura (ignored_blockers):
--   1. Add `status` to the select permission for `anonymous` and `user`,
--      and to the insert permission for `user`.
--   2. Add an update permission for `user`: column `status` only, no row
--      check (matches the existing insert/delete permissions).
-- db/hasura-metadata.json has the result.
--
-- Safe to re-run. Adding a nullable column with no default does not rewrite
-- the table. The CHECK is added NOT VALID and validated separately, so the
-- brief exclusive lock isn't held while the table is scanned. lock_timeout
-- makes the ALTERs give up rather than queue scan webhooks behind them if a
-- long-running query holds the table: just run the file again.

SET lock_timeout = '5s';

ALTER TABLE public.ignored_blockers
    ADD COLUMN IF NOT EXISTS status text;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ignored_blockers_status_check') THEN
        ALTER TABLE public.ignored_blockers
            ADD CONSTRAINT ignored_blockers_status_check
            CHECK (status IS NULL OR status IN ('false_positive', 'wont_fix', 'third_party', 'needs_review', 'fixed_pending'))
            NOT VALID;
    END IF;
END $$;

ALTER TABLE public.ignored_blockers
    VALIDATE CONSTRAINT ignored_blockers_status_check;

RESET lock_timeout;
