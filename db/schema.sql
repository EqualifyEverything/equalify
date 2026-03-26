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
-- Name: hdb_catalog; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA hdb_catalog;


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: gen_hasura_uuid(); Type: FUNCTION; Schema: hdb_catalog; Owner: -
--

CREATE FUNCTION hdb_catalog.gen_hasura_uuid() RETURNS uuid
    LANGUAGE sql
    AS $$select gen_random_uuid()$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

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
BEGIN RETURN QUERY
SELECT
  m.content :: text AS key,
  COUNT(*) :: int AS count
FROM
  blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
WHERE
  b.audit_id = search_audit_id
GROUP BY
  m.content
ORDER BY
  count DESC
LIMIT
  row_limit;
END;
$$;


--
-- Name: get_most_common_tags(uuid, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_tags(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN RETURN QUERY
SELECT
  t.content :: text AS key,
  COUNT(*) :: int AS count
FROM
  blockers b
  JOIN blocker_messages bm ON b.id = bm.blocker_id
  JOIN messages m ON bm.message_id = m.id
  JOIN message_tags mt ON m.id = mt.message_id
  JOIN tags t ON mt.tag_id = t.id
WHERE
  b.audit_id = search_audit_id
GROUP BY
  t.content
ORDER BY
  count DESC
LIMIT
  row_limit;
END;
$$;


--
-- Name: get_most_common_urls(uuid, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_most_common_urls(search_audit_id uuid, row_limit integer) RETURNS SETOF public.item_count_template
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
GROUP BY
  u.url
ORDER BY
  count DESC
LIMIT
  row_limit;
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
-- Name: hdb_action_log; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_action_log (
    id uuid DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    action_name text,
    input_payload jsonb NOT NULL,
    request_headers jsonb NOT NULL,
    session_variables jsonb NOT NULL,
    response_payload jsonb,
    errors jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    response_received_at timestamp with time zone,
    status text NOT NULL,
    CONSTRAINT hdb_action_log_status_check CHECK ((status = ANY (ARRAY['created'::text, 'processing'::text, 'completed'::text, 'error'::text])))
);


--
-- Name: hdb_cron_event_invocation_logs; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_cron_event_invocation_logs (
    id text DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    event_id text,
    status integer,
    request json,
    response json,
    created_at timestamp with time zone DEFAULT now()
);


--
-- Name: hdb_cron_events; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_cron_events (
    id text DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    trigger_name text NOT NULL,
    scheduled_time timestamp with time zone NOT NULL,
    status text DEFAULT 'scheduled'::text NOT NULL,
    tries integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    next_retry_at timestamp with time zone,
    CONSTRAINT valid_status CHECK ((status = ANY (ARRAY['scheduled'::text, 'locked'::text, 'delivered'::text, 'error'::text, 'dead'::text])))
);


--
-- Name: hdb_metadata; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_metadata (
    id integer NOT NULL,
    metadata json NOT NULL,
    resource_version integer DEFAULT 1 NOT NULL
);


--
-- Name: hdb_scheduled_event_invocation_logs; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_scheduled_event_invocation_logs (
    id text DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    event_id text,
    status integer,
    request json,
    response json,
    created_at timestamp with time zone DEFAULT now()
);


--
-- Name: hdb_scheduled_events; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_scheduled_events (
    id text DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    webhook_conf json NOT NULL,
    scheduled_time timestamp with time zone NOT NULL,
    retry_conf json,
    payload json,
    header_conf json,
    status text DEFAULT 'scheduled'::text NOT NULL,
    tries integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    next_retry_at timestamp with time zone,
    comment text,
    CONSTRAINT valid_status CHECK ((status = ANY (ARRAY['scheduled'::text, 'locked'::text, 'delivered'::text, 'error'::text, 'dead'::text])))
);


--
-- Name: hdb_schema_notifications; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_schema_notifications (
    id integer NOT NULL,
    notification json NOT NULL,
    resource_version integer DEFAULT 1 NOT NULL,
    instance_id uuid NOT NULL,
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT hdb_schema_notifications_id_check CHECK ((id = 1))
);


--
-- Name: hdb_version; Type: TABLE; Schema: hdb_catalog; Owner: -
--

CREATE TABLE hdb_catalog.hdb_version (
    hasura_uuid uuid DEFAULT hdb_catalog.gen_hasura_uuid() NOT NULL,
    version text NOT NULL,
    upgraded_on timestamp with time zone NOT NULL,
    cli_state jsonb DEFAULT '{}'::jsonb NOT NULL,
    console_state jsonb DEFAULT '{}'::jsonb NOT NULL,
    ee_client_id text,
    ee_client_secret text
);


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
    short_id text
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
-- Name: blocker_summary_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.blocker_summary_view AS
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
     LEFT JOIN public.tags t ON ((mt.tag_id = t.id)));


--
-- Name: ignored_blockers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ignored_blockers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    audit_id uuid NOT NULL,
    blocker_id uuid NOT NULL
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
    processed_pages jsonb DEFAULT jsonb_build_array()
);


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
-- Name: hdb_action_log hdb_action_log_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_action_log
    ADD CONSTRAINT hdb_action_log_pkey PRIMARY KEY (id);


--
-- Name: hdb_cron_event_invocation_logs hdb_cron_event_invocation_logs_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_cron_event_invocation_logs
    ADD CONSTRAINT hdb_cron_event_invocation_logs_pkey PRIMARY KEY (id);


--
-- Name: hdb_cron_events hdb_cron_events_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_cron_events
    ADD CONSTRAINT hdb_cron_events_pkey PRIMARY KEY (id);


--
-- Name: hdb_metadata hdb_metadata_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_metadata
    ADD CONSTRAINT hdb_metadata_pkey PRIMARY KEY (id);


--
-- Name: hdb_metadata hdb_metadata_resource_version_key; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_metadata
    ADD CONSTRAINT hdb_metadata_resource_version_key UNIQUE (resource_version);


--
-- Name: hdb_scheduled_event_invocation_logs hdb_scheduled_event_invocation_logs_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_scheduled_event_invocation_logs
    ADD CONSTRAINT hdb_scheduled_event_invocation_logs_pkey PRIMARY KEY (id);


--
-- Name: hdb_scheduled_events hdb_scheduled_events_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_scheduled_events
    ADD CONSTRAINT hdb_scheduled_events_pkey PRIMARY KEY (id);


--
-- Name: hdb_schema_notifications hdb_schema_notifications_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_schema_notifications
    ADD CONSTRAINT hdb_schema_notifications_pkey PRIMARY KEY (id);


--
-- Name: hdb_version hdb_version_pkey; Type: CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_version
    ADD CONSTRAINT hdb_version_pkey PRIMARY KEY (hasura_uuid);


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
-- Name: hdb_cron_event_invocation_event_id; Type: INDEX; Schema: hdb_catalog; Owner: -
--

CREATE INDEX hdb_cron_event_invocation_event_id ON hdb_catalog.hdb_cron_event_invocation_logs USING btree (event_id);


--
-- Name: hdb_cron_event_status; Type: INDEX; Schema: hdb_catalog; Owner: -
--

CREATE INDEX hdb_cron_event_status ON hdb_catalog.hdb_cron_events USING btree (status);


--
-- Name: hdb_cron_events_unique_scheduled; Type: INDEX; Schema: hdb_catalog; Owner: -
--

CREATE UNIQUE INDEX hdb_cron_events_unique_scheduled ON hdb_catalog.hdb_cron_events USING btree (trigger_name, scheduled_time) WHERE (status = 'scheduled'::text);


--
-- Name: hdb_scheduled_event_status; Type: INDEX; Schema: hdb_catalog; Owner: -
--

CREATE INDEX hdb_scheduled_event_status ON hdb_catalog.hdb_scheduled_events USING btree (status);


--
-- Name: hdb_version_one_row; Type: INDEX; Schema: hdb_catalog; Owner: -
--

CREATE UNIQUE INDEX hdb_version_one_row ON hdb_catalog.hdb_version USING btree (((version IS NOT NULL)));


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
-- Name: hdb_cron_event_invocation_logs hdb_cron_event_invocation_logs_event_id_fkey; Type: FK CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_cron_event_invocation_logs
    ADD CONSTRAINT hdb_cron_event_invocation_logs_event_id_fkey FOREIGN KEY (event_id) REFERENCES hdb_catalog.hdb_cron_events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: hdb_scheduled_event_invocation_logs hdb_scheduled_event_invocation_logs_event_id_fkey; Type: FK CONSTRAINT; Schema: hdb_catalog; Owner: -
--

ALTER TABLE ONLY hdb_catalog.hdb_scheduled_event_invocation_logs
    ADD CONSTRAINT hdb_scheduled_event_invocation_logs_event_id_fkey FOREIGN KEY (event_id) REFERENCES hdb_catalog.hdb_scheduled_events(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict w0UCtZUAkqllrkWsq8VVIBLlGNK3f5ezOdm6EvNhX5O5xGQEfYRD5Am35egQpHu

