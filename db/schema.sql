--
-- PostgreSQL database dump
--

\restrict w0UCtZUAkqllrkWsq8VVIBLlGNK3f5ezOdm6EvNhX5O5xGQEfYRD5Am35egQpHu

-- Dumped from database version 17.5
-- Dumped by pg_dump version 18.3 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: item_count_template; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.item_count_template (
    key text,
    count integer
);


--
-- Name: get_most_common_messages(uuid, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_messages(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
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


--
-- Name: get_most_common_tags(uuid, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_tags(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
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


--
-- Name: get_most_common_urls(uuid, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_urls(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
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


--
-- Name: item_count_with_total_template; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.item_count_with_total_template (
    key text,
    count integer,
    total_count integer
);


--
-- Name: get_most_common_urls_paginated(uuid, integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_urls_paginated(search_audit_id uuid, row_limit integer, row_offset integer) RETURNS SETOF public.item_count_with_total_template
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


--
-- Name: get_most_common_messages_paginated(uuid, integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_messages_paginated(search_audit_id uuid, row_limit integer, row_offset integer) RETURNS SETOF public.item_count_with_total_template
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


--
-- Name: set_current_timestamp_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.set_current_timestamp_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  _new record;
BEGIN
  _new := NEW;
  _new."updated_at" = NOW();
  RETURN _new;
END;
$$;


--
-- Name: audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audits (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid NOT NULL,
    "interval" text NOT NULL,
    scheduled_at timestamp with time zone,
    processed_at timestamp with time zone,
    status text NOT NULL,
    name text NOT NULL,
    payload jsonb,
    response jsonb,
    email_notifications text,
    remote_csv_url text,
    remote_csv_error text
);


--
-- Name: blocker_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blocker_messages (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    message_id uuid NOT NULL,
    blocker_id uuid NOT NULL
);


--
-- Name: blockers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blockers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    audit_id uuid NOT NULL,
    content text NOT NULL,
    content_normalized text NOT NULL,
    content_hash_id uuid NOT NULL,
    targets jsonb DEFAULT jsonb_build_array() NOT NULL,
    equalified boolean DEFAULT false NOT NULL,
    url_id uuid,
    scan_id uuid,
    short_id text,
    url_text text
);


--
-- Name: message_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_tags (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    message_id uuid NOT NULL,
    tag_id uuid NOT NULL
);


--
-- Name: messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.messages (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    content text NOT NULL,
    category text NOT NULL
);


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    content text NOT NULL
);


--
-- Name: urls; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.urls (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid NOT NULL,
    audit_id uuid NOT NULL,
    url text NOT NULL,
    type text NOT NULL,
    audit_ids jsonb DEFAULT jsonb_build_array() NOT NULL
);


--
-- Name: scans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scans (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    audit_id uuid NOT NULL,
    status text,
    errors jsonb DEFAULT jsonb_build_array(),
    percentage numeric DEFAULT '0'::numeric NOT NULL,
    pages jsonb DEFAULT jsonb_build_array(),
    processed_pages jsonb DEFAULT jsonb_build_array(),
    blocker_count integer DEFAULT 0 NOT NULL,
    equalified_count integer DEFAULT 0 NOT NULL
);


--
-- Name: blocker_summary_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.blocker_summary_view AS
 SELECT b.audit_id,
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
     LEFT JOIN public.urls u ON ((b.url_id = u.id))
     LEFT JOIN public.blocker_messages bm ON ((b.id = bm.blocker_id))
     LEFT JOIN public.messages m ON ((bm.message_id = m.id))
     LEFT JOIN public.message_tags mt ON ((m.id = mt.message_id))
     LEFT JOIN public.tags t ON ((mt.tag_id = t.id));


--
-- Name: ignored_blockers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ignored_blockers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    audit_id uuid NOT NULL,
    blocker_id uuid NOT NULL,
    content_hash_id uuid
);


--
-- Name: invites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invites (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid NOT NULL,
    name text,
    email text NOT NULL,
    type text DEFAULT 'member'::text NOT NULL,
    expires_on timestamp with time zone
);


--
-- Name: logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid,
    audit_id uuid,
    message text,
    data jsonb
);


--
-- Name: options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.options (
    key text NOT NULL,
    value text
);


--
-- Name: options options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.options
    ADD CONSTRAINT options_pkey PRIMARY KEY (key);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    name text NOT NULL,
    email text NOT NULL,
    type text DEFAULT 'member'::text,
    analytics jsonb,
    apikey uuid DEFAULT gen_random_uuid() NOT NULL
);


--
-- Name: blocker_llm_summaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blocker_llm_summaries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    blocker_id uuid NOT NULL,
    summary text NOT NULL,
    flagged boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (blocker_id)
);


--
-- Name: blocker_llm_summaries set_public_blocker_llm_summaries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_blocker_llm_summaries_updated_at BEFORE UPDATE ON public.blocker_llm_summaries FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: access_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.access_requests (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    email text NOT NULL,
    name text,
    status text DEFAULT 'pending'::text NOT NULL,
    reviewed_by uuid,
    reviewed_at timestamp with time zone,
    PRIMARY KEY (id)
);


--
-- Name: access_requests set_public_access_requests_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_access_requests_updated_at BEFORE UPDATE ON public.access_requests FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: audits audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_pkey PRIMARY KEY (id);


--
-- Name: blocker_messages blocker_messages_message_id_blocker_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocker_messages
    ADD CONSTRAINT blocker_messages_message_id_blocker_id_key UNIQUE (message_id, blocker_id);


--
-- Name: tags blocker_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT blocker_tags_pkey PRIMARY KEY (id);


--
-- Name: blocker_messages blocker_type_blockers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blocker_messages
    ADD CONSTRAINT blocker_type_blockers_pkey PRIMARY KEY (id);


--
-- Name: message_tags blocker_type_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_tags
    ADD CONSTRAINT blocker_type_tags_pkey PRIMARY KEY (id);


--
-- Name: messages blocker_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT blocker_types_pkey PRIMARY KEY (id);


--
-- Name: invites invites_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invites
    ADD CONSTRAINT invites_email_key UNIQUE (email);


--
-- Name: invites invites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invites
    ADD CONSTRAINT invites_pkey PRIMARY KEY (id);


--
-- Name: ignored_blockers issue_updates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ignored_blockers
    ADD CONSTRAINT issue_updates_pkey PRIMARY KEY (id);


--
-- Name: blockers issues_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blockers
    ADD CONSTRAINT issues_pkey PRIMARY KEY (id);


--
-- Name: logs logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.logs
    ADD CONSTRAINT logs_pkey PRIMARY KEY (id);


--
-- Name: message_tags message_tags_message_id_tag_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_tags
    ADD CONSTRAINT message_tags_message_id_tag_id_key UNIQUE (message_id, tag_id);


--
-- Name: scans scans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scans
    ADD CONSTRAINT scans_pkey PRIMARY KEY (id);


--
-- Name: urls urls_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.urls
    ADD CONSTRAINT urls_pkey PRIMARY KEY (id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: blockers_short_id; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX blockers_short_id ON public.blockers USING btree (audit_id, short_id);


--
-- Name: idx_blocker_messages_blocker_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_blocker_messages_blocker_id ON public.blocker_messages USING btree (blocker_id);


--
-- Name: idx_blocker_messages_message_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_blocker_messages_message_id ON public.blocker_messages USING btree (message_id);


--
-- Name: idx_blockers_audit_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_blockers_audit_id ON public.blockers USING btree (audit_id);


--
-- Name: idx_blockers_scan_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_blockers_scan_id ON public.blockers USING btree (scan_id);


--
-- Name: idx_blockers_url_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_blockers_url_id ON public.blockers USING btree (url_id);


--
-- Name: idx_message_tags_message_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_message_tags_message_id ON public.message_tags USING btree (message_id);


--
-- Name: idx_message_tags_tag_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_message_tags_tag_id ON public.message_tags USING btree (tag_id);


--
-- Name: idx_scans_status_updated; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_scans_status_updated ON public.scans USING btree (status, updated_at);


--
-- Name: idx_urls_audit_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_urls_audit_id ON public.urls USING btree (audit_id);


--
-- Name: audits set_public_audits_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_audits_updated_at BEFORE UPDATE ON public.audits FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_audits_updated_at ON audits; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_audits_updated_at ON public.audits IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: blocker_messages set_public_blocker_type_blockers_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_blocker_type_blockers_updated_at BEFORE UPDATE ON public.blocker_messages FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_blocker_type_blockers_updated_at ON blocker_messages; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_blocker_type_blockers_updated_at ON public.blocker_messages IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: message_tags set_public_blocker_type_tags_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_blocker_type_tags_updated_at BEFORE UPDATE ON public.message_tags FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_blocker_type_tags_updated_at ON message_tags; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_blocker_type_tags_updated_at ON public.message_tags IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: invites set_public_invites_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_invites_updated_at BEFORE UPDATE ON public.invites FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_invites_updated_at ON invites; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_invites_updated_at ON public.invites IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: ignored_blockers set_public_issue_updates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_issue_updates_updated_at BEFORE UPDATE ON public.ignored_blockers FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_issue_updates_updated_at ON ignored_blockers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_issue_updates_updated_at ON public.ignored_blockers IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: blockers set_public_issues_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_issues_updated_at BEFORE UPDATE ON public.blockers FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_issues_updated_at ON blockers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_issues_updated_at ON public.blockers IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: logs set_public_logs_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_logs_updated_at BEFORE UPDATE ON public.logs FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_logs_updated_at ON logs; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_logs_updated_at ON public.logs IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: scans set_public_scans_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_scans_updated_at BEFORE UPDATE ON public.scans FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_scans_updated_at ON scans; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_scans_updated_at ON public.scans IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: urls set_public_urls_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_urls_updated_at BEFORE UPDATE ON public.urls FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_urls_updated_at ON urls; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_urls_updated_at ON public.urls IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- Name: users set_public_users_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_public_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();


--
-- Name: TRIGGER set_public_users_updated_at ON users; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TRIGGER set_public_users_updated_at ON public.users IS 'trigger to set value of column "updated_at" to current timestamp on row update';


--
-- PostgreSQL database dump complete
--

\unrestrict w0UCtZUAkqllrkWsq8VVIBLlGNK3f5ezOdm6EvNhX5O5xGQEfYRD5Am35egQpHu

