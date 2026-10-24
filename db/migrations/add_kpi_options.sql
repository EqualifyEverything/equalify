-- Platform KPI settings and the daily KPI snapshot live in the options table
-- (key/value), so no schema change is needed. See docs/technical/kpis.md.
--
--   kpi_red_sites_total  the denominator for Red adoption: live, public UIC Red
--                        sites per the Red team. Maintained by hand - update
--                        `total` and `asOf` whenever the Red team sends a refresh.
--   kpi_snapshot         written daily by runEveryDay (adoption, conformance,
--                        remediation figures). Never edit by hand.
--   kpi_red_hosts        per-host Red theme probe cache used by the snapshot.
--
-- Safe to re-run: seeds the denominator only if it isn't set yet.

INSERT INTO public.options ("key", "value")
VALUES ('kpi_red_sites_total', '{"total": 690, "asOf": "2026-10-22", "source": "Michael LaRue (UIC Red team): public sites not marked archived or deleted"}')
ON CONFLICT ("key") DO NOTHING;
