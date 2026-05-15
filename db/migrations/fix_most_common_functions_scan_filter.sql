-- Each rescan inserts new blocker rows rather than updating existing ones, so
-- querying by audit_id alone counts all historical scans and inflates results.
-- These replacements scope each function and the blocker_summary_view to the
-- latest scan per audit.
--
-- get_most_common_messages and get_most_common_tags also switch to
-- COUNT(DISTINCT b.id) to avoid double-counting a blocker that is linked to
-- multiple message records that share the same content string (possible when
-- different test codes produce identical descriptions).

CREATE OR REPLACE VIEW public.blocker_summary_view AS
 SELECT b.audit_id,
    u.url,
    m.content AS message_content,
    m.category,
    t.content AS tag_content
   FROM (((((public.blockers b
     LEFT JOIN public.urls u ON ((b.url_id = u.id)))
     LEFT JOIN public.blocker_messages bm ON ((b.id = bm.blocker_id)))
     LEFT JOIN public.messages m ON ((bm.message_id = m.id)))
     LEFT JOIN public.message_tags mt ON ((m.id = mt.message_id)))
     LEFT JOIN public.tags t ON ((mt.tag_id = t.id)))
  WHERE (b.scan_id = ( SELECT scans.id
           FROM public.scans
          WHERE (scans.audit_id = b.audit_id)
          ORDER BY scans.created_at DESC
         LIMIT 1));


CREATE OR REPLACE FUNCTION public.get_most_common_messages(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN RETURN QUERY
SELECT
  m.content :: text AS key,
  COUNT(DISTINCT b.id) :: int AS count
FROM
  blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
WHERE
  b.audit_id = search_audit_id
  AND b.scan_id = (SELECT id FROM scans WHERE audit_id = search_audit_id ORDER BY created_at DESC LIMIT 1)
GROUP BY
  m.content
ORDER BY
  count DESC
LIMIT
  row_limit;
END;
$$;


CREATE OR REPLACE FUNCTION public.get_most_common_tags(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN RETURN QUERY
SELECT
  t.content :: text AS key,
  COUNT(DISTINCT b.id) :: int AS count
FROM
  blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
  JOIN message_tags mt ON m.id = mt.message_id
  JOIN tags t ON mt.tag_id = t.id
WHERE
  b.audit_id = search_audit_id
  AND b.scan_id = (SELECT id FROM scans WHERE audit_id = search_audit_id ORDER BY created_at DESC LIMIT 1)
GROUP BY
  t.content
ORDER BY
  count DESC
LIMIT
  row_limit;
END;
$$;


CREATE OR REPLACE FUNCTION public.get_most_common_urls(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN RETURN QUERY
SELECT
  u.url :: text AS key,
  COUNT(*) :: int AS count
FROM
  blockers b
  JOIN urls u ON b.url_id = u.id
WHERE
  b.audit_id = search_audit_id
  AND b.scan_id = (SELECT id FROM scans WHERE audit_id = search_audit_id ORDER BY created_at DESC LIMIT 1)
GROUP BY
  u.url
ORDER BY
  count DESC
LIMIT
  row_limit;
END;
$$;
