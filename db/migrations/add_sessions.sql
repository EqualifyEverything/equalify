-- sessions records one row per authenticated app load or login
-- (trackSession.ts, called by the frontend after the backend accepts the
-- user's token). It backs the month-over-month KPIs on the admin
-- Account > Statistics tab (getSystemStats.ts): sessions started, active
-- users, and units served (distinct `department`, captured from Microsoft
-- Graph for SSO users). IF NOT EXISTS keeps this safe on fresh installs
-- where schema.sql already includes the table.

CREATE TABLE IF NOT EXISTS public.sessions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid NOT NULL,
    auth_method text,
    department text,
    analytics jsonb,
    PRIMARY KEY (id)
);

CREATE INDEX IF NOT EXISTS sessions_created_at_idx ON public.sessions (created_at);
CREATE INDEX IF NOT EXISTS sessions_user_id_created_at_idx ON public.sessions (user_id, created_at);
