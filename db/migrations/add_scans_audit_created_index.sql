-- Most reads of scans look up an audit's latest scan(s)
-- (`WHERE audit_id = $1 ORDER BY created_at DESC LIMIT n`), and the Tag
-- Report routes (getTagReport*.ts) do that once per audit, per chart day.
-- Without this index each lookup scans every row for the audit.

CREATE INDEX IF NOT EXISTS scans_audit_id_created_at_idx ON public.scans (audit_id, created_at DESC);
