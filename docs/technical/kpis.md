# Platform KPIs

Equalify reports platform usage and accessibility figures in four places: the admin **Statistics** panel (Account → Statistics), the public `GET /public/getPublicStats` endpoint that the Equalify Hub's About page and the DASE Hub read, and the monthly development reports. Every figure is computed in one module, `apps/backend/utils/kpis.ts`, so those surfaces always agree. This page is the plain-language definition of each figure; keep it in sync with that file.

## Where the numbers come from

| Kind | Computed | Source |
|------|----------|--------|
| **Live** | On request (routes cache for 5 minutes) | SQL over `users`, `audits`, `urls`, `scans`, `blockers`, `sessions`; CloudWatch `scansStarted` / `ScanDuration` metrics for page scans |
| **Monthly** | On request, one row per UTC calendar month | Same sources, bucketed by month. The last row is the current, partial month (`partial: true`); hubs showing a snapshot should use the last row with `partial: false` |
| **Snapshot** | Once a day by `runEveryDay`, stored in `options.kpi_snapshot` | SQL over the live and archived blocker tables plus a homepage probe for Red sites. Saved after each piece, so a piece that fails or runs out of time keeps its previous value without affecting the others. The job logs `kpi snapshot: updated [...] skipped [...] failed [...]` |

## Usage

| Figure | Definition |
|--------|------------|
| Total users / new users | Accounts in Equalify; created in the window |
| Active users | Distinct accounts with a session (sign-in or app load) in the window. Sessions are recorded from the Sep 2026 release onward |
| Units served | Distinct SSO departments with a session in the window. SSO logins only |
| Audit owners | Distinct accounts that have created at least one audit |
| Total audits / new audits | Audits created |
| Pages tracked | Rows in `urls` across all audits |
| Scan runs | Audit-level scan records created in the window |
| Pages scanned (HTML / PDF) | Page and document scans started, from the scanner Lambdas' `scansStarted` metric |
| Average scan time | Mean `ScanDuration` metric over the window |
| Total blockers / new blockers | Rows in the live `blockers` table (each audit's latest scan) |

## Adoption (Title II)

| Figure | Definition |
|--------|------------|
| uic.edu hosts tracked | Distinct `*.uic.edu` hostnames with at least one tracked page, excluding the Red team's own platform hosts (`red.uic.edu` and subdomains) |
| UIC Red sites using Equalify | Of those hosts, the ones whose homepage references the `uic-red` WordPress theme. Each host is probed at most weekly, up to 300 hosts per daily run (the probes also stop when the daily job's time budget runs short), so a newly added host usually shows up the next day (later if the daily job ran short of time). The Equalify Hub only shows the Red share once no more than a tenth of tracked hosts are still waiting for their first check |
| Red sites scanned (30 days) | Red hosts in an audit with a completed scan in the last 30 days |
| Red site owners | Distinct accounts owning an audit that tracks a Red host |
| Red site total | The denominator: live, public Red sites per the Red team. Maintained by hand in `options.kpi_red_sites_total` (see below); 690 as of Oct 22, 2026 |

## Conformance (Title II)

| Figure | Definition |
|--------|------------|
| Open blockers | Live blockers that are not ignored |
| Open blockers by level | A blocker's level is the strictest WCAG level its rule is tagged with: Level A if tagged `wcag2a` (or `wcag21a`, `wcag22a`), else AA, else AAA. Rules with no WCAG level tag are best-practice checks |
| Pages with no open blockers | Pages tracked minus pages with at least one open blocker. A page that has never been scanned counts as having none, so treat this as an upper bound |

## Remediation

| Figure | Definition |
|--------|------------|
| Audits rescanned | Audits with a completed scan in the month that also had one before the month began. Audits created during the month are left out, so a first scan isn't counted as all-new issues |
| Issues resolved | For each of those audits, distinct issues (by content hash) in its last scan before the month that are absent from its last scan of the month |
| New issues | The reverse: in the month's last scan but not in the last scan before the month |

Scans closed out by the stuck-scan sweep (`scan_timeout`) are ignored on both sides, because their results are partial and an unscanned page would otherwise look resolved. So are scans from before June 2026, which were archived before the archive kept every row (the same cutoff as Scan History). Both scans are read from the live and archived tables. A finished month's figure depends only on scans up to its end, so it doesn't change no matter when the report is generated. Pages added to or removed from an audit during the month also move these numbers, so treat them as an indicator, not an exact count of fixes.

The snapshot carries this for the current month to date (`remediation`) and for the last full month (`remediationPreviousMonth`); the monthly report prints the latter.

## Settings in the `options` table

| Key | Written by | Contents |
|-----|------------|----------|
| `kpi_red_sites_total` | By hand | `{"total": 690, "asOf": "2026-10-22", "source": "..."}`. Update `total` and `asOf` whenever the Red team sends a refresh (SQL or the Hasura console) |
| `kpi_snapshot` | `runEveryDay` | The daily adoption / conformance / remediation figures. Never edit |
| `kpi_red_hosts` | `runEveryDay` | Per-host theme probe cache. Never edit |

`db/migrations/add_kpi_options.sql` seeds the denominator on an install that doesn't have it.

## Consumers

- **Admin Statistics panel** (`getSystemStats`): live figures, six months of monthly rows with charts, and the snapshot.
- **`GET /public/getPublicStats`**: the same live figures, thirteen months of monthly rows, the snapshot and the Red site total. Totals only, cached five minutes. The flat top-level fields (`totalUsers`, `activeUsers30d`, ...) are kept for existing consumers.
- **Equalify Hub About page**: shows the last full month, with the live figures behind "Live right now".
- **Monthly development report**: the generator fetches the endpoint fresh (never a stale cached copy) and prints the reporting month beside the three months before it, plus the Title II figures from the snapshot.
