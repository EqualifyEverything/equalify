This page is intended for **Project Leads, Department Heads, and anyone tracking several audits at once**. A Tag Report combines every audit with the same tag into one view, so you can follow blockers across a whole department, college, or group of websites instead of one audit at a time. For how to create and apply tags, see [🔗 "Organizing audits with tags"](https://equalify.uic.edu/dashboard/user-guide/2-configuring-and-running-audits/2-5-organizing-audits-with-tags).

## Opening a Tag Report
- **From the Audits page:** Select any tag on an audit card or in the **Tags** column of the table view.
- **From Account > Tags (Admins):** Select the **chart** icon next to a tag. See [🔗 "Managing tags"](https://equalify.uic.edu/dashboard/user-guide/5-administration/5-5-managing-tags).

Tag Reports are available to signed-in users only. People viewing a [🔗 shared report](https://equalify.uic.edu/dashboard/user-guide/3-interpreting-audit-data-and-dashboards/3-5-notifications-reporting-and-sharing) can see an audit's tags, but can't open Tag Reports.

```important
⚠️Important: A Tag Report always reflects the audits that have the tag today, including their full history. Adding an audit to a tag adds its past results to the report's chart, and removing an audit takes its past results out. If you need a fixed record for a reporting period, note the numbers at the end of that period.
```

## Scan status notice
If any audit in the report is scanning right now, its last scan failed, or it hasn't completed a scan yet, a notice appears at the top of the page. While that's the case, the totals and chart use each audit's most recent **completed** scan, so an audit that's mid-scan doesn't make the combined count dip.

## Audits table
The **Audits** table lists every audit with the tag, with the audits contributing the most blockers first.

| Column | Description |
|---|---|
| Name | The audit's name. Select it to open the audit. |
| Blockers | The number of blockers found in the audit's most recent completed scan. |
| Change | How the audit's blocker count changed over the date range selected for the chart (for example, "12 fewer"). "New" means the audit had no completed scan before the start of the range. |
| Last Completed Scan | The date of the audit's most recent completed scan. "Scanning now" or "Last scan failed" appears below the date when relevant. |
| URLs | The number of URLs in the audit. |
| Runs | How often the audit is scanned. |
| Created By | The person who created the audit. |
| Actions | A button to remove the audit from the tag. |

### Adding audits to the tag
1. In **Add audits to this tag**, above the table, start typing an audit's name. Only audits that don't already have the tag are listed.
2. Select one or more audits.
3. Select **Add Audit** (or **Add N Audits**).

The audits are tagged, and their results are added to the report right away. This is the same as adding the tag from each audit's own page.

### Removing an audit from the tag
1. Select the **trash can** icon in the audit's row.
2. Confirm by selecting **Remove**.

The tag is removed from the audit, and its results, including its past results, no longer count toward the report. The audit itself, and its scans, are not affected.

Adding and removing audits are both recorded on the Logs page, as an entry for each audit that changed. See [🔗 "What gets logged"](https://equalify.uic.edu/dashboard/user-guide/4-compliance-and-logs/4-2-what-gets-logged-and-the-importance-of-audit-logs).

## Combined blockers chart
The chart shows the total number of blockers across all the tagged audits over time.
- **Date Range:** Choose Week, Month, Quarter, or Year. The range also sets the period for the **Change** column and the change shown on the Blockers Found card.
- **How the line is calculated:** For each day, each audit contributes the blocker count from its most recent completed scan up to that day. Audits are scanned on different schedules, so the line steps up or down on the days any of them complete a scan.
- **Dots** mark the days on which at least one audit completed a scan. Hover over a point to see the combined count and how many scans completed that day.
- **Table View** lists the same data as text: each day a scan completed, the combined blocker count, and the number of scans completed.

## Summary cards
- **Blockers Found:** The combined number of blockers across the tagged audits, how that number has changed over the selected date range, and how many audits are included.
- **URLs with blockers:** How many of the URLs scanned across these audits have at least one blocker, as a count and a percentage, with a breakdown of PDF and HTML blockers.

## URLs with Most Blockers
Lists the pages with the most blockers across all the tagged audits, along with the audit each page belongs to.
- Select a URL to open that audit's Detailed View, filtered to the page. Select the audit name below it to open the audit.
- Select the **external link** icon to open the page itself in a new tab.
- Use **Previous** and **Next** to browse the full list.

```tip
💡Tip: If the same page is included in more than one tagged audit, it's listed once for each audit, because each audit scans it separately. The combined totals count it once per audit, too.
```
