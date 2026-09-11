This page is intended for **Developers and Accessibility Specialists** tasked with fixing specific code-level issues. Switch to the Audit Report detailed view for the "under-the-hood" companion to your summary view. While the summary tells you how many problems you have, this view tells a developer exactly where they are and how to fix them.
## Filtering & Search: 
The **Search by URL** field stays visible at the top of the page. The rest of the filters are tucked behind a **Show Advanced Filter Options** toggle to keep the page uncluttered — click it to reveal them.
- **Filter by Status:** Choose between **Active** issues (currently live on the site), **Ignored** issues (manually hidden), and **All** recorded issues. Each option shows a live count of matching blockers.
- Use the **Filter by Accessibility Standard** or **Filter by Rules** dropdowns to narrow the list by type (e.g., a specific WCAG success criterion, or a rule category like contrast or ARIA roles).
```tip
💡Tip: Use the Accessibility Standard filter to check if your content meets a specific compliance level. Equalify uses AxeCore for web and Vera for PDF rulesets — see those for full technical details. The dropdown groups options by standard, with plain-language labels rather than raw codes:
- **WCAG:** Broad conformance levels (e.g., "WCAG 2.1 Level AA") as well as individual success criteria (e.g., "WCAG 1.4.3 Contrast (Minimum)").
- **Section 508, EN 301 549, and RGAA:** Regional/regulatory standards, shown with their specific clause or paragraph.
- **Trusted Tester:** DHS Trusted Tester test references.
Only standards that actually apply to your data appear in the list.
```
- **Content Type filter:** Quickly switch between issues found in HTML (web) versus those found within PDF documents, or view all. Each option shows a live count of matching blockers.<br>
- **Show/hide columns:** Tailor the view to show or hide audit details.<br>
- **Download the report** as a CSV file, which is helpful for teams that want to manage issues in issue-tracking software (e.g., JIRA, Rally). Click the download icon to choose from:
    - **Export filtered blockers:** Only the blockers matching your current filters and search.
    - **Export all blockers:** Every blocker in the audit, ignoring any filters currently applied.
    - **Export PDF Source Page URLs:** A separate export listing the HTML pages that link out to PDF documents, alongside those PDF URLs — useful for tracking down where a flagged PDF is referenced from. Only available when viewing your own audits, not on shared report links.<br>
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/46ed941ba70a82efbf5bddc737451c34a90fff83/user/User%20Guide%20Images/Sec%203_Audit%20Detail%20View%20Filtering%20and%20Search.png" alt="Audit Report Detailed View header with Active/ Ignored / All tabs, and All / HTML / PDF filter buttons, Filter by Tags, Filter by Categories, and Search by URL field." width="500"/><br>
## Detailed Issue List
This central table lists every accessibility violation, including the specific URL where it occurred and a plain-English explanation of the WCAG (Web Content Accessibility Guidelines) rule violated.
Important Details for Developers: To move from identifying a problem to fixing it, focus on these three technical components provided in each row:
- **Description:** High-level description of what the problem is.
- **ID:** Each error is assigned a unique code (e.g., UWQ330 or WKN817), shown in its own column right after Type. These serve as reference points for tracking fixes across different scans. You can open the issue or copy its link.
- **Issue Detail page:** When you click on the ID, Equalify provides a deep dive into the issue to help you resolve it quickly.
    - **Appears on:** What page or PDF does this blocker appear on.
    - **Audit:** What audit did this arise from.
    - **Error:** High-level description of what the issue is.
    - **Rule:** What rule does this blocker fall under.
    - **Accessibility Standards:** What accessibility standards (WCAG, Section 508, EN 301 549, RGAA, Trusted Tester) this blocker relates to, if any.
    - **Code Snippet:** Equalify extracts the exact HTML block causing the flag.
```important
⚠️Important: For PDFs: Equalify can not point you to the specific page where an issue occurs, as it cannot identify the exact snippet.
```
- **Code**
    - **View Code button:** Clicking View Code opens a snippet of the page's source code, highlighting the exact line to modify.
    - **Issue Element:** This identifies the specific HTML element affected by the issue.
- **Rule:** What rule does this blocker fall under.
- **Accessibility Standards:** What accessibility standards this blocker relates to, if any.
- **Ignore:** Toggle an issue on/off for the report.<br>
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/46ed941ba70a82efbf5bddc737451c34a90fff83/user/User%20Guide%20Images/Sec%203_Dashboard%20Audit%20Report%20Detailed%20View.png" alt="Detailed audit results table listing Type, URL, Issue, and Code columns with View Code buttons for each issue." width="500"/>

## AI-Generated Blocker Summary
When you open an Issue Detail page, Equalify displays an **AI-generated Blocker Summary** below the issue details. This summary is intended to help you understand and fix the issue without needing to look up the WCAG rule yourself.

The summary includes:
- A **plain-language explanation** of what the accessibility issue is and why it matters to users.
- **Step-by-step instructions** for fixing the issue in your code.

```important
⚠️Important: This summary is generated by an AI and may contain errors. Always verify the suggested fix against your site's specific implementation before applying it.
```

**Actions available on the summary:**
- **Reload summary:** Regenerates the summary from scratch. Use this if the summary seems incorrect or you want a fresh response.
- **Flag a problem with this summary:** If the summary contains inaccurate or unhelpful content, use this to report it. This helps improve the quality of future summaries.

```tip
💡Tip: If no summary appears, click "Reload summary" to generate one. Summaries are cached after the first load, so subsequent visits to the same issue will be faster.
```
