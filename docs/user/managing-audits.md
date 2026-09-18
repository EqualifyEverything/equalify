# Managing Audits

Audits are at the core of Equalify. This guide covers how to create, configure, and run audits.

## Creating a New Audit
1.  Navigate to the Audits page.
2.  Click **Add New Audit** (or similar button).
3.  **Name**: Give your audit a descriptive name (e.g., "Department Website").
4.  **Frequency**: Choose how often the scan should run:
    - Manual
    - Daily
    - Weekly
    - Monthly

## Adding URLs

Unlike many accessibility tools that crawl your site automatically, Equalify uses a **URL-based approach** — you provide the specific pages and documents you want scanned. This gives you full control over what's included in each audit and ensures consistent, reproducible results every scan.

You can add URLs to your audit in two ways:

### 1. Manual Entry
- Enter the URL (e.g., `help.uiillinois.edu`).
- Click **Add URL**.
- Equalify will attempt to identify the type (HTML or PDF). You can adjust this if needed.

### 2. Bulk Upload (CSV)
For scanning many documents at once, use the CSV upload feature.
1.  Download the standard CSV template.
2.  The CSV requires two columns:
    - `URL`: The link to the document.
    - `Type`: Either `PDF` or `HTML`.
3.  Upload the completed CSV.
4.  The system will categorize the items automatically.

> **Tip**: You can get a list of all pages on your site from your CMS, your sitemap.xml file, or a tool like Google Analytics, and then import them into Equalify via CSV.

**Important**: Equalify can only scan content that is **publicly accessible**. It cannot scan pages behind authentication layers.

## Running an Audit
- When you are ready, click **Save and Run Audit**.
- The scan time depends on the number and type of documents but is generally quick.
- Once complete, you will see the number of blockers identified.
