This page is intended for **Administrators** who need a high-level view of platform usage and scan activity. The Statistics tab is only visible to Admin users and is accessed from **Account > Statistics**.

## Overview
The Statistics page shows workspace activity in six groups: users and audits, adoption, accessibility issues and conformance, remediation, recent scan performance, and month-over-month trends. The same figures feed the public Equalify Hub and the monthly development reports, so the numbers match wherever you see them. Use it to monitor platform health, track growth, and report on ADA Title II progress.

## Users & Audits
| Metric | Description |
|---|---|
| Total Users | The number of accounts in the workspace. Includes a count of new users added in the last 30 days. |
| Active Users (30 days) | Accounts that signed in or opened the app in the last 30 days, with the number of distinct units (SSO departments) they belong to. |
| Total Audits | The total number of audits created in the workspace. Includes a count of new audits created in the last 30 days. |
| Audit Owners | Accounts that have created at least one audit, with the number of audits scanning right now. |
| Total Pages Tracked | The total number of individual URLs across all audits in the workspace. |

## Adoption (Title II)
These figures are recomputed once a day.

| Metric | Description |
|---|---|
| UIC Red Sites Using Equalify | How many live UIC Red sites have at least one page tracked in Equalify, shown against the Red team's total and as a percentage. A site counts as Red when its homepage uses the uic-red WordPress theme. |
| Red Sites Scanned (30 days) | Red sites that completed a scan in the last 30 days. |
| Red Site Owners (Equalify accounts) | Distinct accounts that own an audit tracking a Red site. |
| uic.edu Hosts Tracked | All uic.edu hostnames with a tracked page, Red or not. Newly added hosts are usually checked for the Red theme in the next daily run. |

The Red site total comes from the Red team and is updated by hand; the date it was last refreshed is shown under the cards.

## Accessibility Issues & Conformance
| Metric | Description |
|---|---|
| Total Blockers Found | The cumulative number of accessibility issues detected across all scans. Includes a count of new blockers discovered in the last 30 days. |
| Pages With No Open Blockers | The share of tracked pages with no open (non-ignored) blocker in their audit's latest scan. Pages that have never been scanned count as clear, so read this as an upper bound. |
| Open Blockers, WCAG Level A / AA | Open blockers whose rule is tagged with that WCAG level. Level A is counted first, so a blocker appears in one row only. |
| Open Blockers, AAA / Best Practice | Open blockers for Level AAA criteria or best-practice rules without a WCAG level. |

## Remediation
| Metric | Description |
|---|---|
| Issues Resolved | For each audit scanned in the month, distinct issues that were present before the month began and are gone by the month's last scan. Shown for the last full month and for the current month to date. Audits created during the month and scans that timed out are left out. |
| New Issues | The reverse: issues in the month's last scan that weren't there before the month began. |

## Scans (Last 30 Days)
This section reflects scan activity over the rolling 30-day window.

| Metric | Description |
|---|---|
| Scan Runs | Audit-level scans started, with how many failed. |
| HTML Pages Scanned | The number of individual HTML page scans started. |
| PDF Documents Scanned | The number of individual PDF document scans started. |
| Avg HTML Scan Time | The average time to complete a single HTML page scan. Displayed as milliseconds or seconds. |
| Avg PDF Scan Time | The average time to complete a single PDF document scan. Displayed as milliseconds or seconds. |

```tip
💡Tip: Average scan times are shown as "N/A" if no scans of that type completed in the last 30 days.
```

## Monthly KPIs
Two charts (sessions and active users; pages scanned) and a table show the last six calendar months, with the current month marked as "to date". Sessions, active users and units served are recorded from the September 2026 release onward, so earlier months show 0.
