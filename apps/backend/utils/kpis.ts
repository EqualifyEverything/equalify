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
// Enough to check every tracked host in one run (~190 on prod), so the first
// snapshot after a deploy is complete instead of filling in over several days.
// The probe loop also stops on its own when the caller's time budget runs short.
const RED_PROBE_MAX_PER_RUN = 300;
const RED_PROBE_CONCURRENCY = 8;
const RED_PROBE_TIMEOUT_MS = 6000;
// Each snapshot query gets its own statement timeout so one slow piece can't
// eat the whole daily run; a timed-out piece keeps its previous value.
const SNAPSHOT_QUERY_TIMEOUT = '25s';
// Scans archived before the stale_blockers table existed (June 2026) kept only part
// of their blockers, so they can't serve as a remediation baseline. Same cutoff as
// the Detailed View's Scan History picker (apps/frontend/src/routes/Audit.tsx).
const ARCHIVE_COMPLETE_SINCE = '2026-06-01T00:00:00Z';

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
    auditsRescanned: number;      // audits scanned in the month that also had a scan before it (see computeRemediation)
    resolvedIssues: number;       // distinct issues present before the month that are gone by its last scan
    newIssues: number;            // distinct issues in the month's last scan that weren't there before it
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

// Only a positive total is a usable denominator; anything else reads as "not set"
export const getRedSitesTotal = async (): Promise<RedSitesTotal | null> => {
    const v = await getOption<RedSitesTotal>(OPTION_RED_SITES_TOTAL);
    return v && typeof v.total === 'number' && v.total > 0 ? v : null;
};
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

async function computeAdoption(hasTimeFor: (ms: number) => boolean): Promise<KpiSnapshot['adoption']> {
    // Every tracked uic.edu host, with the accounts that own an audit tracking it,
    // and the hosts with a completed scan in the last 30 days
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
        // one batch (bounded by the probe timeout, which also covers reading the body) plus the writes and cleanup after the loop
        if (!hasTimeFor(RED_PROBE_TIMEOUT_MS + 15_000)) { console.log('kpi snapshot: stopping Red probes early (out of time)'); break; }
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
    // One statement, so every figure comes from the same snapshot of the live
    // table. Levels are derived per message (rule), which is tiny; blockers are
    // then grouped (a blocker can carry more than one message) and a blocker's
    // level is the strictest one it is tagged with: A if any Level A tag, else
    // AA, else AAA. Rules with no WCAG level tag are best-practice checks.
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
            leveled AS (
                SELECT b.id, b.url_id,
                       COALESCE(bool_or(ml.level_a), false)   AS level_a,
                       COALESCE(bool_or(ml.level_aa), false)  AS level_aa,
                       COALESCE(bool_or(ml.level_aaa), false) AS level_aaa
                FROM blockers b
                LEFT JOIN blocker_messages bm ON bm.blocker_id = b.id
                LEFT JOIN message_levels ml ON ml.id = bm.message_id
                WHERE NOT EXISTS (SELECT 1 FROM ignored_blockers ib WHERE ib.blocker_id = b.id)
                GROUP BY b.id, b.url_id
            )
            SELECT
                (SELECT COUNT(*) FROM urls)                                        AS monitored_pages,
                COUNT(*)                                                           AS open_blockers,
                COUNT(DISTINCT url_id)                                             AS pages_with_blockers,
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

// Remediation for one UTC month, per audit: the audit's last completed scan in the
// month (E) is compared with its last completed scan before the month began (B),
// by content hash. Resolved = in B but not in E; new = in E but not in B. Audits
// with no B (created during the month) are left out, so a new audit's first scan
// doesn't count as hundreds of new issues. Scans the stuck-scan sweep closed out
// (scan_timeout) are skipped: their results are partial, and a page that was never
// scanned would otherwise count as resolved. So are scans before ARCHIVE_COMPLETE_SINCE. Either scan may already have been
// archived, so both tables are read. Because E and B only depend on scans before
// the month's end, a finished month's figure is stable no matter when it's read.
// `monthsBack` 0 = the current (partial) month, 1 = the last full month.
async function computeRemediation(monthsBack: number): Promise<RemediationKpi> {
    const win = monthWindows(monthsBack + 1)[0];
    const r = (await db.query({
        text: `
            WITH usable AS (
                SELECT id, audit_id, created_at FROM scans
                WHERE status = 'complete'
                  AND created_at >= $3
                  AND NOT (COALESCE(errors, '[]'::jsonb) @> '[{"type":"scan_timeout"}]'::jsonb)
            ),
            e AS (
                SELECT DISTINCT ON (audit_id) id, audit_id FROM usable
                WHERE created_at >= $1 AND created_at < $2
                ORDER BY audit_id, created_at DESC
            ),
            b AS (
                SELECT DISTINCT ON (u.audit_id) u.id, u.audit_id FROM usable u JOIN e ON e.audit_id = u.audit_id
                WHERE u.created_at < $1
                ORDER BY u.audit_id, u.created_at DESC
            ),
            pairs AS (
                SELECT e.audit_id, e.id AS e_id, b.id AS b_id FROM e JOIN b USING (audit_id)
            ),
            b_hashes AS (
                SELECT p.audit_id, x.content_hash_id FROM pairs p JOIN blockers x       ON x.scan_id = p.b_id
                UNION
                SELECT p.audit_id, x.content_hash_id FROM pairs p JOIN stale_blockers x ON x.scan_id = p.b_id
            ),
            e_hashes AS (
                SELECT p.audit_id, x.content_hash_id FROM pairs p JOIN blockers x       ON x.scan_id = p.e_id
                UNION
                SELECT p.audit_id, x.content_hash_id FROM pairs p JOIN stale_blockers x ON x.scan_id = p.e_id
            )
            SELECT
                (SELECT COUNT(*) FROM pairs) AS audits_rescanned,
                (SELECT COUNT(*) FROM b_hashes bh
                   WHERE NOT EXISTS (SELECT 1 FROM e_hashes eh WHERE eh.audit_id = bh.audit_id AND eh.content_hash_id = bh.content_hash_id)) AS resolved,
                (SELECT COUNT(*) FROM e_hashes eh
                   WHERE NOT EXISTS (SELECT 1 FROM b_hashes bh WHERE bh.audit_id = eh.audit_id AND bh.content_hash_id = eh.content_hash_id)) AS new_issues
        `,
        values: [win.start.toISOString(), win.end.toISOString(), ARCHIVE_COMPLETE_SINCE],
    })).rows[0];
    return {
        month: win.month,
        auditsRescanned: num(r.audits_rescanned),
        resolvedIssues: num(r.resolved),
        newIssues: num(r.new_issues),
        computedAt: new Date().toISOString(),
    };
}

export interface KpiSnapshotRun {
    snapshot: KpiSnapshot;
    updated: string[];  // pieces recomputed this run
    skipped: string[];  // not started: the caller's time budget couldn't cover them
    failed: string[];   // started but threw (timeout, error); previous value kept
}

/**
 * Recomputes the daily KPI snapshot under OPTION_KPI_SNAPSHOT, one piece at a
 * time. The snapshot is saved after every piece that succeeds, so if the run is
 * cut short (out of budget, or the Lambda is killed) only the piece in flight is
 * lost and every other piece keeps its newest value. Each statement runs under
 * a session statement timeout. Expects db.connect() to have been called.
 */
export async function computeKpiSnapshot(hasTimeFor: (ms: number) => boolean = () => true): Promise<KpiSnapshotRun> {
    const previous: KpiSnapshot = { computedAt: '', adoption: null, conformance: null, remediation: null, remediationPreviousMonth: null, ...((await getKpiSnapshot()) ?? {}) };
    let snapshot: KpiSnapshot = { ...previous };
    const run: Omit<KpiSnapshotRun, 'snapshot'> = { updated: [], skipped: [], failed: [] };
    // A piece starts only if the caller's budget covers its statements at the full
    // statement timeout plus headroom (adoption runs two small queries; its homepage
    // probes then stop on their own when time runs short, so it goes last). Measured
    // at prod scale the pieces take 2-5 s each, so these gates only bite on days the
    // archive ran long. The last full month's remediation goes before the current
    // month's because the monthly report needs it.
    const pieces: [keyof Omit<KpiSnapshot, 'computedAt'>, number, () => Promise<any>][] = [
        ['conformance',              30_000, computeConformance],
        ['remediationPreviousMonth', 30_000, () => computeRemediation(1)],
        ['remediation',              30_000, () => computeRemediation(0)],
        ['adoption',                 55_000, () => computeAdoption(hasTimeFor)],
    ];
    try {
        for (const [name, needsMs, compute] of pieces) {
            if (!hasTimeFor(needsMs)) { run.skipped.push(name); continue; }
            try {
                // A session setting rather than a transaction on purpose: no transaction
                // state can be left open on the shared connection, and Lambda re-initializes
                // the container after a timeout. Re-applied per piece in case the client
                // reconnected in between (a new connection starts without it).
                await db.query({ text: `SET statement_timeout = '${SNAPSHOT_QUERY_TIMEOUT}'` });
                const next: KpiSnapshot = { ...snapshot, [name]: await compute(), computedAt: new Date().toISOString() };
                await setOption(OPTION_KPI_SNAPSHOT, next);
                snapshot = next; // only once it's saved, so the log and the stored value agree
                run.updated.push(name);
            } catch (error) {
                run.failed.push(name);
                console.error(`kpi snapshot: ${name} failed, keeping previous value`, error);
            }
        }
    } finally {
        await db.query({ text: 'RESET statement_timeout' }).catch(() => undefined);
    }
    console.log(`kpi snapshot: updated [${run.updated.join(', ')}] skipped [${run.skipped.join(', ')}] failed [${run.failed.join(', ')}]`);
    return { snapshot, ...run };
}
