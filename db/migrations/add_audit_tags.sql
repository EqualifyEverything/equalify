-- audit_tags / audit_tag_assignments back user-created labels for
-- organizing audits (AuditTagInput on the Audit Builder and Audit pages,
-- the tag filter on the Audits screen, and the admin Account > Tags tab).
-- Deliberately separate from public.tags / message_tags, which hold the
-- axe-core rule tags (wcag2aa, cat.aria, ...) attached to blocker messages.
-- Tags are shared across the whole install, matching audits being visible
-- to every user. IF NOT EXISTS keeps this safe on fresh installs where
-- schema.sql already includes the tables; the foreign keys live only here
-- (schema.sql has none) so they're added for both fresh and existing installs.

CREATE TABLE IF NOT EXISTS public.audit_tags (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    name text NOT NULL,
    created_by uuid,
    PRIMARY KEY (id)
);

-- Case-insensitive uniqueness, so "Library" and "library" can't coexist
CREATE UNIQUE INDEX IF NOT EXISTS audit_tags_name_lower_key ON public.audit_tags (lower(name));

DROP TRIGGER IF EXISTS set_public_audit_tags_updated_at ON public.audit_tags;
CREATE TRIGGER set_public_audit_tags_updated_at BEFORE UPDATE ON public.audit_tags FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();

CREATE TABLE IF NOT EXISTS public.audit_tag_assignments (
    audit_id uuid NOT NULL,
    tag_id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    created_by uuid,
    PRIMARY KEY (audit_id, tag_id)
);

CREATE INDEX IF NOT EXISTS audit_tag_assignments_tag_id_idx ON public.audit_tag_assignments (tag_id);

-- Cascades clean up assignments when an audit (deleteAudit.ts) or a tag
-- (deleteAuditTag.ts / mergeAuditTags.ts) is removed.
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'audit_tag_assignments_audit_id_fkey') THEN
        ALTER TABLE public.audit_tag_assignments
            ADD CONSTRAINT audit_tag_assignments_audit_id_fkey FOREIGN KEY (audit_id) REFERENCES public.audits(id) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'audit_tag_assignments_tag_id_fkey') THEN
        ALTER TABLE public.audit_tag_assignments
            ADD CONSTRAINT audit_tag_assignments_tag_id_fkey FOREIGN KEY (tag_id) REFERENCES public.audit_tags(id) ON DELETE CASCADE;
    END IF;
END $$;
