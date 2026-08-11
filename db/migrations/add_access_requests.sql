-- access_requests backs the "Request access" flow (requestAccess.ts,
-- getAccessRequests.ts, reviewAccessRequest.ts — the admin-only
-- Account > Requests tab) for SSO users without an Equalify account yet.
-- Missing from the base schema.sql dump for installs created before this
-- was added; IF NOT EXISTS makes this safe to run on a fresh install too,
-- where schema.sql already includes the table.

CREATE TABLE IF NOT EXISTS public.access_requests (
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

DROP TRIGGER IF EXISTS set_public_access_requests_updated_at ON public.access_requests;
CREATE TRIGGER set_public_access_requests_updated_at BEFORE UPDATE ON public.access_requests FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();
