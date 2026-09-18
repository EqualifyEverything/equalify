-- Backs paginated "URLs with Most Blockers" / "Most Common Blockers" cards
-- (previously hard-capped to the top 5). New functions rather than altering
-- get_most_common_urls/get_most_common_messages in place, since changing
-- their return columns would require dropping the originals first — this
-- way is non-destructive and leaves existing callers untouched.
--
-- total_count is computed via COUNT(*) OVER(), which Postgres evaluates
-- against the full grouped result set before LIMIT/OFFSET are applied, so
-- every row on every page carries the same correct total.

CREATE TABLE IF NOT EXISTS public.item_count_with_total_template (
    key text,
    count integer,
    total_count integer
);

CREATE OR REPLACE FUNCTION public.get_most_common_urls_paginated(search_audit_id uuid, row_limit integer, row_offset integer) RETURNS SETOF public.item_count_with_total_template
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
  latest_scan_id uuid;
BEGIN
  SELECT id INTO latest_scan_id
  FROM scans
  WHERE audit_id = search_audit_id
  ORDER BY created_at DESC
  LIMIT 1;

  RETURN QUERY
  SELECT
    u.url::text AS key,
    COUNT(*)::int AS count,
    COUNT(*) OVER ()::int AS total_count
  FROM blockers b
  JOIN urls u ON b.url_id = u.id
  WHERE b.scan_id = latest_scan_id
  GROUP BY u.url
  ORDER BY count DESC
  LIMIT row_limit OFFSET row_offset;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_most_common_messages_paginated(search_audit_id uuid, row_limit integer, row_offset integer) RETURNS SETOF public.item_count_with_total_template
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
  latest_scan_id uuid;
BEGIN
  SELECT id INTO latest_scan_id
  FROM scans
  WHERE audit_id = search_audit_id
  ORDER BY created_at DESC
  LIMIT 1;

  RETURN QUERY
  SELECT
    m.content::text AS key,
    COUNT(DISTINCT b.id)::int AS count,
    COUNT(*) OVER ()::int AS total_count
  FROM blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
  WHERE b.scan_id = latest_scan_id
  GROUP BY m.content
  ORDER BY count DESC
  LIMIT row_limit OFFSET row_offset;
END;
$$;
