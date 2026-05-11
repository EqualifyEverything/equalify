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

CREATE TRIGGER set_public_blocker_llm_summaries_updated_at
    BEFORE UPDATE ON public.blocker_llm_summaries
    FOR EACH ROW EXECUTE FUNCTION public.set_current_timestamp_updated_at();
