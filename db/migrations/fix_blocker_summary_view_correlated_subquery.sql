-- blocker_summary_view previously filtered to the latest scan per audit using a
-- correlated subquery (WHERE b.scan_id = (SELECT ... WHERE audit_id = b.audit_id)).
-- That subquery re-executed once per blocker row, making the view O(n) on scans.
-- Replacing it with a DISTINCT ON derived table resolves the latest scan per audit
-- once, then joins — same semantics, much cheaper on large audits.
--
-- The get_most_common_* functions referenced search_audit_id (a constant parameter)
-- in their subquery, so PostgreSQL could hoist it, but resolving it into a local
-- variable makes the intent explicit and guarantees a single lookup.

CREATE OR REPLACE VIEW public.blocker_summary_view AS
SELECT
  b.audit_id,
  u.url,
  m.content AS message_content,
  m.category,
  t.content AS tag_content
FROM public.blockers b
JOIN (
  SELECT DISTINCT ON (audit_id) id
  FROM public.scans
  ORDER BY audit_id, created_at DESC
) latest_scan ON b.scan_id = latest_scan.id
LEFT JOIN public.urls u ON b.url_id = u.id
LEFT JOIN public.blocker_messages bm ON b.id = bm.blocker_id
LEFT JOIN public.messages m ON bm.message_id = m.id
LEFT JOIN public.message_tags mt ON m.id = mt.message_id
LEFT JOIN public.tags t ON mt.tag_id = t.id;


CREATE OR REPLACE FUNCTION public.get_most_common_messages(search_audit_id uuid, row_limit integer)
  RETURNS SETOF public.item_count_template
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
    COUNT(DISTINCT b.id)::int AS count
  FROM blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
  WHERE b.scan_id = latest_scan_id
  GROUP BY m.content
  ORDER BY count DESC
  LIMIT row_limit;
END;
$$;


CREATE OR REPLACE FUNCTION public.get_most_common_tags(search_audit_id uuid, row_limit integer)
  RETURNS SETOF public.item_count_template
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
    t.content::text AS key,
    COUNT(DISTINCT b.id)::int AS count
  FROM blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
  JOIN message_tags mt ON m.id = mt.message_id
  JOIN tags t ON mt.tag_id = t.id
  WHERE b.scan_id = latest_scan_id
  GROUP BY t.content
  ORDER BY count DESC
  LIMIT row_limit;
END;
$$;


CREATE OR REPLACE FUNCTION public.get_most_common_urls(search_audit_id uuid, row_limit integer)
  RETURNS SETOF public.item_count_template
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
    COUNT(*)::int AS count
  FROM blockers b
  JOIN urls u ON b.url_id = u.id
  WHERE b.scan_id = latest_scan_id
  GROUP BY u.url
  ORDER BY count DESC
  LIMIT row_limit;
END;
$$;
