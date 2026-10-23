import { db } from './db';
import { CloudWatchClient, GetMetricStatisticsCommand } from '@aws-sdk/client-cloudwatch';

// ─── Platform KPIs ───────────────────────────────────────────────────────────
// Every platform KPI Equalify reports is computed here and nowhere else, so the
// admin Statistics panel (getSystemStats), the public endpoint the hubs read
// (getPublicStats) and the monthly development report always agree. Plain-
// language definitions live in docs/technical/kpis.md; keep the two in sync.
//
// Three kinds of figure:
//   live      cheap SQL + CloudWatch, computed on request (cached by the routes)
//   monthly   one row per UTC calendar month, same sources
//   snapshot  the expensive Title II figures (Red adoption, conformance,
//             remediation), computed once a day by runEveryDay and stored in the
//             options table so reads are free

export const OPTION_KPI_SNAPSHOT    = 'kpi_snapshot';         // JSON KpiSnapshot
export const OPTION_RED_HOSTS       = 'kpi_red_hosts';        // JSON { [host]: { isRed, checkedAt } }
export const OPTION_RED_SITES_TOTAL = 'kpi_red_sites_total';  // JSON RedSitesTotal, maintained by hand

const NAMESPACE = process.env.POWERTOOLS_METRICS_NAMESPACE ?? 'equalifyuic';
const cloudwatch = new CloudWatchClient({ region: process.env.AWS_REGION ?? 'us-east-2' });

// A Red site's homepage references the uic-red WordPress theme. Audit names are
// not reliable (many Red sites live in audits that don't say "RED").
const RED_THEME_MARKER = 'wp-content/themes/uic-red';
// Platform, test and dev hosts of the Red team itself - never unit sites
const RED_PLATFORM_HOSTS = /(^|\.)red\.uic\.edu$/i;
const UIC_HOST = /^([a-z0-9-]+\.)*uic\.edu$/i;
const RED_PROBE_TTL_MS = 7 * 24 * 60 * 60 * 1000; // re-check a host weekly
const RED_PROBE_MAX_PER_RUN = 40;                   // keeps a daily run bounded (~40 x 6 s / 8 parallel)
const RED_PROBE_CONCURRENCY = 8;
const RED_PROBE_TIMEOUT_MS = 6000;
// Each snapshot query gets its own statement timeout so one slow piece can't
// eat the whole daily run; a timed-out piece keeps its previous value.
const SNAPSHOT_QUERY_TIMEOUT = '25s';

export interface LiveKpis {
    totalUsers: number;
    newUsers30d: number;
    activeUsers30d: number;   // distinct accounts with a session in the last 30 days
    unitsServed30d: number;   // distinct SSO departments with a session in the last 30 days
    auditOwners: number;      // distinct accounts that have created at least one audit
    totalAudits: number;
    newAudits30d: number;
    activeAudits: number;     // audits currently scanning
    totalUrls: number;        // pages tracked across all audits
    totalBlockers: number;    // live blockers table (latest scan of every audit)
    newBlockers30d: number;
    scanRuns30d: number;      // audit-level scans started
    failedScans30d: number;
    htmlScans30d: number;     // page scans started (CloudWatch scansStarted)
    pdfScans30d: number;      // document scans started (CloudWatch scansStarted)
    avgHtmlScanDurationMs30d: number;
    avgPdfScanDurationMs30d: number;
}

// One row per UTC calendar month, oldest first. `partial` marks the current month.
export interface MonthlyKpi {
    month: string;        // YYYY-MM
    sessions: number;     // authenticated app loads / logins (sessions table)
    activeUsers: number;  // distinct users with a session that month
    unitsServed: number;  // distinct SSO departments with a session that month
    scanRuns: number;     // audit-level scan records
    htmlScans: number;    // page scans started (CloudWatch)
    pdfScans: number;     // document scans started (CloudWatch)
    newUsers: number;
    newAudits: number;
    partial: boolean;
}

export interface RedSitesTotal {
    total: number;    // live, public Red sites per the Red team (690 as of Oct 22, 2026)
    asOf: string;     // YYYY-MM-DD the Red team gave the number
    source?: string;
}

export interface KpiSnapshot {
    computedAt: string;
    adoption: {
        uicHostsTracked: number;      // distinct *.uic.edu hosts with at least one tracked page
        redSitesTracked: number;      // of those, hosts running the Red theme (platform hosts excluded)
        redSitesScanned30d: number;   // Red hosts in an audit with a completed scan in the last 30 days
        redSiteOwners: number;        // distinct accounts owning an audit that tracks a Red host
        redHostsProbed: number;       // hosts with a fresh theme check (the rest are still pending)
        redHostsPending: number;
        computedAt: string;
    } | null;
    conformance: {
        monitoredPages: number;       // pages tracked (urls)
        pagesWithBlockers: number;    // pages with at least one live, non-ignored blocker
        conformantPages: number;      // monitoredPages - pagesWithBlockers
        conformantShare: number;      // 0..1
        openBlockers: number;         // live, non-ignored blockers
        openBlockersLevelA: number;   // tagged WCAG Level A
        openBlockersLevelAA: number;  // tagged Level AA (and not A)
        openBlockersLevelAAA: number; // tagged Level AAA only
        openBlockersUntagged: number; // best-practice rules with no WCAG level tag
        computedAt: string;
    } | null;
    remediation: RemediationKpi | null;              // the current (partial) month
    remediationPreviousMonth: RemediationKpi | null; // the last full month - what the monthly report prints
}

export interface RemediationKpi {
    month: string;                // the month this row describes (UTC)
    auditsRescanned: number;      // audits whose latest completed scan happened that month and that had an earlier completed scan to compare with
    resolvedIssues: number;       // distinct issues in those audits' previous scan that are gone from the latest
    newIssues: number;            // distinct issues in the latest scan that weren't in the previous one
    computedAt: string;
}

// ─── Shared helpers ──────────────────────────────────────────────────────────

interface MonthWindow { month: string; start: Date; end: Date }

// Whole UTC calendar months, oldest first, ending with the current (partial) month.
// Whole months keep the CloudWatch period a multiple of 3600, which is required
// for any query whose start is more than 63 days in the past.
export function monthWindows(count: number): MonthWindow[] {
    const now = new Date();
    const windows: MonthWindow[] = [];
    for (let i = count - 1; i >= 0; i--) {
        const start = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth() - i, 1));
        const end = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 1, 1));
        windows.push({ month: start.toISOString().slice(0, 7), start, end });
    }
    return windows;
}

async function getCwMetric(serviceName: string, metricName: string, stat: 'Sum' | 'Average', startTime: Date, endTime: Date): Promise<number> {
    const res = await cloudwatch.send(new GetMetricStatisticsCommand({
        Namespace: NAMESPACE,
        MetricName: metricName,
        Dimensions: [{ Name: 'service', Value: serviceName }],
        StartTime: startTime,
        EndTime: endTime,
        // Single bucket spanning the whole window - one aggregate value, not a time series
        Period: Math.round((endTime.getTime() - startTime.getTime()) / 1000),
        Statistics: [stat],
    }));
    const point = res.Datapoints?.[0];
    if (!point) return 0;
    return stat === 'Sum' ? (point.Sum ?? 0) : (point.Average ?? 0);
}

const num = (v: any): number => (v === null || v === undefined ? 0 : Number(v));

export async function getOption<T>(key: string): Promise<T | null> {
    const row = (await db.query({ text: `SELECT "value" FROM "options" WHERE "key" = $1`, values: [key] })).rows[0];
    if (!row?.value) return null;
    try { return JSON.parse(row.value) as T; } catch { return null; }
}

export async function setOption(key: string, value: unknown): Promise<void> {
    await db.query({
        text: `INSERT INTO "options" ("key", "value") VALUES ($1, $2)
               ON CONFLICT ("key") DO UPDATE SET "value" = EXCLUDED."value"`,
        values: [key, JSON.stringify(value)],
    });
}

// ─── Live + monthly (cheap) ──────────────────────────────────────────────────

export async function getLiveKpis(): Promise<LiveKpis> {
    const now = new Date();
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const [dbResult, htmlScans, pdfScans, htmlAvg, pdfAvg] = await Promise.all([
        db.query({
            text: `
                SELECT
                    (SELECT COUNT(*) FROM users)                                                                AS total_users,
                    (SELECT COUNT(*) FROM users    WHERE created_at >= NOW() - INTERVAL '30 days')             AS new_users_30d,
                    (SELECT COUNT(DISTINCT user_id)    FROM sessions WHERE created_at >= NOW() - INTERVAL '30 days') AS active_users_30d,
                    (SELECT COUNT(DISTINCT department) FROM sessions WHERE created_at >= NOW() - INTERVAL '30 days'
                                                                       AND department IS NOT NULL)             AS units_served_30d,
                    (SELECT COUNT(DISTINCT user_id) FROM audits)                                                AS audit_owners,
                    (SELECT COUNT(*) FROM audits)                                                               AS total_audits,
                    (SELECT COUNT(*) FROM audits   WHERE created_at >= NOW() - INTERVAL '30 days')             AS new_audits_30d,
                    (SELECT COUNT(*) FROM audits   WHERE status = 'processing')                                 AS active_audits,
                    (SELECT COUNT(*) FROM urls)                                                                 AS total_urls,
                    (SELECT COUNT(*) FROM blockers)                                                             AS total_blockers,
                    (SELECT COUNT(*) FROM blockers WHERE created_at >= NOW() - INTERVAL '30 days')             AS new_blockers_30d,
                    (SELECT COUNT(*) FROM scans    WHERE created_at >= NOW() - INTERVAL '30 days')             AS scan_runs_30d,
                    (SELECT COUNT(*) FROM scans    WHERE status = 'failed'
                                                     AND created_at >= NOW() - INTERVAL '30 days')             AS failed_scans_30d
            `,
        }),
        getCwMetric('aws-lambda-scan-html', 'scansStarted', 'Sum',     thirtyDaysAgo, now),
        getCwMetric('aws-lambda-scan-pdf',  'scansStarted', 'Sum',     thirtyDaysAgo, now),
        getCwMetric('aws-lambda-scan-html', 'ScanDuration', 'Average', thirtyDaysAgo, now),
        getCwMetric('aws-lambda-scan-pdf',  'ScanDuration', 'Average', thirtyDaysAgo, now),
    ]);
    const r = dbResult.rows[0];
    return {
        totalUsers: num(r.total_users),
        newUsers30d: num(r.new_users_30d),
        activeUsers30d: num(r.active_users_30d),
        unitsServed30d: num(r.units_served_30d),
        auditOwners: num(r.audit_owners),
        totalAudits: num(r.total_audits),
        newAudits30d: num(r.new_audits_30d),
        activeAudits: num(r.active_audits),
        totalUrls: num(r.total_urls),
        totalBlockers: num(r.total_blockers),
        newBlockers30d: num(r.new_blockers_30d),
        scanRuns30d: num(r.scan_runs_30d),
        failedScans30d: num(r.failed_scans_30d),
        htmlScans30d: htmlScans,
        pdfScans30d: pdfScans,
        avgHtmlScanDurationMs30d: htmlAvg,
        avgPdfScanDurationMs30d: pdfAvg,
    };
}

// Sessions (and therefore active users / units served) only exist from the
// release that added the sessions table (Sep 2026) onward; earlier months read 0.
export async function getMonthlyKpis(months: number): Promise<MonthlyKpi[]> {
    const windows = monthWindows(months);
    const [monthlyResult, html, pdf] = await Promise.all([
        db.query({
            text: `
                WITH months AS (
                    SELECT generate_series(
                        (date_trunc('month', NOW() AT TIME ZONE 'UTC') AT TIME ZONE 'UTC') - ($1::int - 1) * INTERVAL '1 month',
                        (date_trunc('month', NOW() AT TIME ZONE 'UTC') AT TIME ZONE 'UTC'),
                        INTERVAL '1 month'
                    ) AS m
                )
                SELECT
                    to_char(m AT TIME ZONE 'UTC', 'YYYY-MM') AS month,
                    (SELECT COUNT(*)                     FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month') AS sessions,
                    (SELECT COUNT(DISTINCT s.user_id)    FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month') AS active_users,
                    (SELECT COUNT(DISTINCT s.department) FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month'
                                                                             AND s.department IS NOT NULL)                                  AS units_served,
                    (SELECT COUNT(*) FROM scans  sc WHERE sc.created_at >= m AND sc.created_at < m + INTERVAL '1 month')                  AS scan_runs,
                    (SELECT COUNT(*) FROM users  u  WHERE u.created_at  >= m AND u.created_at  < m + INTERVAL '1 month')                  AS new_users,
                    (SELECT COUNT(*) FROM audits a  WHERE a.created_at  >= m AND a.created_at  < m + INTERVAL '1 month')                  AS new_audits
                FROM months
                ORDER BY m
            `,
            values: [months],
        }),
        Promise.all(windows.map((w) => getCwMetric('aws-lambda-scan-html', 'scansStarted', 'Sum', w.start, w.end))),
        Promise.all(windows.map((w) => getCwMetric('aws-lambda-scan-pdf',  'scansStarted', 'Sum', w.start, w.end))),
    ]);
    const byMonth = new Map<string, any>(monthlyResult.rows.map((r: any) => [r.month, r]));
    return windows.map((w, i) => {
        const row = byMonth.get(w.month) ?? {};
        return {
            month: w.month,
            sessions: num(row.sessions),
            activeUsers: num(row.active_users),
            unitsServed: num(row.units_served),
            scanRuns: num(row.scan_runs),
            htmlScans: html[i],
            pdfScans: pdf[i],
            newUsers: num(row.new_users),
            newAudits: num(row.new_audits),
            partial: i === windows.length - 1,
        };
    });
}

export const getRedSitesTotal = () => getOption<RedSitesTotal>(OPTION_RED_SITES_TOTAL);
export const getKpiSnapshot   = () => getOption<KpiSnapshot>(OPTION_KPI_SNAPSHOT);

// ─── Daily snapshot (expensive) ──────────────────────────────────────────────

const hostOf = (url: string): string | null => {
    const m = /^https?:\/\/([^/:?#]+)/i.exec(url);
    return m ? m[1].toLowerCase() : null;
};

type RedHostCache = Record<string, { isRed: boolean; checkedAt: string }>;

// Does this host's homepage use the Red theme? Network errors are not an answer:
// the host stays pending and is retried on a later run.
async function probeRedTheme(host: string): Promise<boolean | null> {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), RED_PROBE_TIMEOUT_MS);
    try {
        const res = await fetch(`https://${host}/`, {
            signal: controller.signal,
            redirect: 'follow',
            headers: { 'User-Agent': 'Equalify-KPI/1.0 (+https://equalify.uic.edu)' },
        });
        if (!res.ok) return false;
        const html = (await res.text()).slice(0, 400_000);
        return html.includes(RED_THEME_MARKER);
    } catch {
        return null;
    } finally {
        clearTimeout(timer);
    }
}

async function computeAdoption(): Promise<KpiSnapshot['adoption']> {
    // Every tracked uic.edu host, with the accounts that own an audit tracking it
    const rows: { host: string; user_id: string }[] = (await db.query({
        text: `SELECT DISTINCT lower(substring(u.url FROM '^https?://([^/:?#]+)')) AS host, a.user_id
               FROM urls u JOIN audits a ON a.id = u.audit_id
               WHERE u.url ~* '^https?://([a-z0-9-]+\\.)*uic\\.edu([/:?#]|$)'`,
    })).rows;
    const recentRows: { host: string }[] = (await db.query({
        text: `SELECT DISTINCT lower(substring(u.url FROM '^https?://([^/:?#]+)')) AS host
               FROM urls u
               JOIN scans s ON s.audit_id = u.audit_id
               WHERE s.status = 'complete' AND s.created_at >= NOW() - INTERVAL '30 days'
                 AND u.url ~* '^https?://([a-z0-9-]+\\.)*uic\\.edu([/:?#]|$)'`,
    })).rows;

    const hosts = new Set<string>();
    const ownersByHost = new Map<string, Set<string>>();
    for (const r of rows) {
        if (!r.host || !UIC_HOST.test(r.host) || RED_PLATFORM_HOSTS.test(r.host)) continue;
        hosts.add(r.host);
        if (!ownersByHost.has(r.host)) ownersByHost.set(r.host, new Set());
        ownersByHost.get(r.host)!.add(r.user_id);
    }
    const recentHosts = new Set(recentRows.map((r) => r.host));

    // Incremental theme probe: new hosts first, then the stalest, at most N per run
    const cache: RedHostCache = (await getOption<RedHostCache>(OPTION_RED_HOSTS)) ?? {};
    const now = Date.now();
    const due = [...hosts]
        .filter((h) => !cache[h] || now - Date.parse(cache[h].checkedAt) > RED_PROBE_TTL_MS)
        .sort((a, b) => (cache[a] ? Date.parse(cache[a].checkedAt) : 0) - (cache[b] ? Date.parse(cache[b].checkedAt) : 0))
        .slice(0, RED_PROBE_MAX_PER_RUN);
    for (let i = 0; i < due.length; i += RED_PROBE_CONCURRENCY) {
        const batch = due.slice(i, i + RED_PROBE_CONCURRENCY);
        const results = await Promise.all(batch.map(probeRedTheme));
        batch.forEach((host, j) => {
            if (results[j] !== null) cache[host] = { isRed: results[j] as boolean, checkedAt: new Date(now).toISOString() };
        });
    }
    // Forget hosts no longer tracked so the cache can't grow forever
    for (const h of Object.keys(cache)) if (!hosts.has(h)) delete cache[h];
    await setOption(OPTION_RED_HOSTS, cache);

    const redHosts = [...hosts].filter((h) => cache[h]?.isRed);
    const owners = new Set<string>();
    for (const h of redHosts) for (const o of ownersByHost.get(h) ?? []) owners.add(o);

    return {
        uicHostsTracked: hosts.size,
        redSitesTracked: redHosts.length,
        redSitesScanned30d: redHosts.filter((h) => recentHosts.has(h)).length,
        redSiteOwners: owners.size,
        redHostsProbed: [...hosts].filter((h) => cache[h]).length,
        redHostsPending: [...hosts].filter((h) => !cache[h]).length,
        computedAt: new Date().toISOString(),
    };
}

async function computeConformance(): Promise<KpiSnapshot['conformance']> {
    // Levels are derived per message (rule), which is tiny, before touching the
    // large blockers table. A blocker's level is the strictest one it is tagged
    // with: A if any Level A tag, else AA, else AAA; best-practice rules have none.
    const r = (await db.query({
        text: `
            WITH message_levels AS (
                SELECT m.id,
                       bool_or(t.content ~ '^wcag2\\d*a$')   AS level_a,
                       bool_or(t.content ~ '^wcag2\\d*aa$')  AS level_aa,
                       bool_or(t.content ~ '^wcag2\\d*aaa$') AS level_aaa
                FROM messages m
                LEFT JOIN message_tags mt ON mt.message_id = m.id
                LEFT JOIN tags t ON t.id = mt.tag_id
                GROUP BY m.id
            ),
            live AS (
                SELECT b.id, b.url_id
                FROM blockers b
                WHERE NOT EXISTS (SELECT 1 FROM ignored_blockers ib WHERE ib.blocker_id = b.id)
            ),
            leveled AS (
                SELECT l.id, bool_or(ml.level_a) AS level_a, bool_or(ml.level_aa) AS level_aa, bool_or(ml.level_aaa) AS level_aaa
                FROM live l
                JOIN blocker_messages bm ON bm.blocker_id = l.id
                JOIN message_levels ml ON ml.id = bm.message_id
                GROUP BY l.id
            )
            SELECT
                (SELECT COUNT(*) FROM urls)                                        AS monitored_pages,
                (SELECT COUNT(DISTINCT url_id) FROM live WHERE url_id IS NOT NULL) AS pages_with_blockers,
                (SELECT COUNT(*) FROM live)                                        AS open_blockers,
                COUNT(*) FILTER (WHERE level_a)                                    AS level_a,
                COUNT(*) FILTER (WHERE level_aa AND NOT level_a)                   AS level_aa,
                COUNT(*) FILTER (WHERE level_aaa AND NOT level_a AND NOT level_aa) AS level_aaa
            FROM leveled
        `,
    })).rows[0];
    const monitored = num(r.monitored_pages), withBlockers = num(r.pages_with_blockers), open = num(r.open_blockers);
    const a = num(r.level_a), aa = num(r.level_aa), aaa = num(r.level_aaa);
    return {
        monitoredPages: monitored,
        pagesWithBlockers: withBlockers,
        conformantPages: Math.max(0, monitored - withBlockers),
        conformantShare: monitored > 0 ? Math.max(0, monitored - withBlockers) / monitored : 0,
        openBlockers: open,
        openBlockersLevelA: a,
        openBlockersLevelAA: aa,
        openBlockersLevelAAA: aaa,
        openBlockersUntagged: Math.max(0, open - a - aa - aaa),
        computedAt: new Date().toISOString(),
    };
}

// Issues are compared by content hash per audit: an issue counts as resolved when
// it was in the previous completed scan and is absent from the latest one. Only
// audits that have a previous completed scan take part, so a brand-new audit's
// first scan doesn't show up as hundreds of "new" issues.
// `monthsBack` 0 = the current (partial) month, 1 = the last full month.
async function computeRemediation(monthsBack: number): Promise<RemediationKpi> {
    const win = monthWindows(monthsBack + 1)[0];
    const r = (await db.query({
        text: `
            WITH latest AS (
                SELECT DISTINCT ON (audit_id) id, audit_id, created_at
                FROM scans WHERE status = 'complete'
                ORDER BY audit_id, created_at DESC
            ),
            in_month AS (
                SELECT * FROM latest WHERE created_at >= $1 AND created_at < $2
            ),
            prev AS (
                SELECT DISTINCT ON (s.audit_id) s.id, s.audit_id
                FROM scans s JOIN in_month m ON m.audit_id = s.audit_id AND s.created_at < m.created_at
                WHERE s.status = 'complete'
                ORDER BY s.audit_id, s.created_at DESC
            ),
            rescanned AS (
                SELECT m.* FROM in_month m JOIN prev p ON p.audit_id = m.audit_id
            ),
            prev_hashes AS (
                SELECT DISTINCT p.audit_id, sb.content_hash_id FROM prev p JOIN stale_blockers sb ON sb.scan_id = p.id
                UNION
                SELECT DISTINCT p.audit_id, b.content_hash_id  FROM prev p JOIN blockers b ON b.scan_id = p.id
            ),
            latest_hashes AS (
                SELECT DISTINCT b.audit_id, b.content_hash_id FROM rescanned m JOIN blockers b ON b.scan_id = m.id
            )
            SELECT
                (SELECT COUNT(*) FROM rescanned) AS audits_rescanned,
                (SELECT COUNT(*) FROM prev_hashes ph
                   WHERE NOT EXISTS (SELECT 1 FROM latest_hashes lh WHERE lh.audit_id = ph.audit_id AND lh.content_hash_id = ph.content_hash_id)) AS resolved,
                (SELECT COUNT(*) FROM latest_hashes lh
                   WHERE NOT EXISTS (SELECT 1 FROM prev_hashes ph WHERE ph.audit_id = lh.audit_id AND ph.content_hash_id = lh.content_hash_id)) AS new_issues
        `,
        values: [win.start.toISOString(), win.end.toISOString()],
    })).rows[0];
    return {
        month: win.month,
        auditsRescanned: num(r.audits_rescanned),
        resolvedIssues: num(r.resolved),
        newIssues: num(r.new_issues),
        computedAt: new Date().toISOString(),
    };
}

/**
 * Recomputes the daily KPI snapshot and stores it under OPTION_KPI_SNAPSHOT.
 * Each piece runs under its own statement timeout and keeps its previous value
 * if it fails or if `hasTimeFor(ms)` says the caller's budget is spent, so the
 * snapshot degrades gracefully instead of blocking the job that runs it.
 * Expects db.connect() to have been called.
 */
export async function computeKpiSnapshot(hasTimeFor: (ms: number) => boolean = () => true): Promise<KpiSnapshot> {
    const previous: KpiSnapshot = { computedAt: '', adoption: null, conformance: null, remediation: null, remediationPreviousMonth: null, ...((await getKpiSnapshot()) ?? {}) };
    const snapshot: KpiSnapshot = { ...previous, computedAt: new Date().toISOString() };
    const pieces: [keyof Omit<KpiSnapshot, 'computedAt'>, number, () => Promise<any>][] = [
        ['adoption',     35_000, computeAdoption],
        ['conformance',  30_000, computeConformance],
        ['remediation',              30_000, () => computeRemediation(0)],
        ['remediationPreviousMonth', 30_000, () => computeRemediation(1)],
    ];
    await db.query({ text: `SET statement_timeout = '${SNAPSHOT_QUERY_TIMEOUT}'` });
    try {
        for (const [name, needsMs, compute] of pieces) {
            if (!hasTimeFor(needsMs)) { console.log(`kpi snapshot: skipped ${name} (out of time)`); continue; }
            try {
                (snapshot as any)[name] = await compute();
            } catch (error) {
                console.error(`kpi snapshot: ${name} failed, keeping previous value`, error);
            }
        }
    } finally {
        await db.query({ text: `SET statement_timeout = DEFAULT` }).catch(() => undefined);
    }
    await setOption(OPTION_KPI_SNAPSHOT, snapshot);
    return snapshot;
}
