import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import {
    BarChart,
    Bar,
    LineChart,
    Line,
    XAxis,
    YAxis,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from "recharts";
import { SkeletonTable } from "./Skeleton";
import themeVariables from "../global-styles/variables.module.scss";
import style from "./SystemStats.module.scss";

// Shapes mirror apps/backend/utils/kpis.ts - the one place every figure is defined.
// One row per calendar month (UTC), oldest first; the last row is the current, partial month
interface MonthlyKpi {
    month: string;       // YYYY-MM
    sessions: number;    // authenticated app loads / logins (sessions table)
    activeUsers: number; // distinct users with a session that month
    unitsServed: number; // distinct SSO departments with a session that month
    scanRuns: number;    // audit-level scan records
    htmlScans: number;   // page scans started (CloudWatch)
    pdfScans: number;    // document scans started (CloudWatch)
    newUsers: number;
    newAudits: number;
    partial: boolean;
}

interface KpiSnapshot {
    computedAt: string;
    adoption: {
        uicHostsTracked: number;
        redSitesTracked: number;
        redSitesScanned30d: number;
        redSiteOwners: number;
        redHostsProbed: number;
        redHostsPending: number;
        computedAt: string;
    } | null;
    conformance: {
        monitoredPages: number;
        pagesWithBlockers: number;
        conformantPages: number;
        conformantShare: number;
        openBlockers: number;
        openBlockersLevelA: number;
        openBlockersLevelAA: number;
        openBlockersLevelAAA: number;
        openBlockersUntagged: number;
        computedAt: string;
    } | null;
    remediation: RemediationKpi | null;              // current month to date
    remediationPreviousMonth: RemediationKpi | null; // last full month
}

interface RemediationKpi {
    month: string;
    auditsRescanned: number;
    resolvedIssues: number;
    newIssues: number;
    computedAt: string;
}

interface SystemStatsData {
    totalUsers: number;
    newUsers30d: number;
    activeUsers30d: number;
    unitsServed30d: number;
    auditOwners: number;
    totalAudits: number;
    newAudits30d: number;
    activeAudits: number;
    totalUrls: number;
    totalBlockers: number;
    newBlockers30d: number;
    scanRuns30d: number;
    failedScans30d: number;
    htmlScans30d: number;
    pdfScans30d: number;
    avgHtmlScanDurationMs30d: number;
    avgPdfScanDurationMs30d: number;
    monthly?: MonthlyKpi[];
    snapshot?: KpiSnapshot | null;
    redSitesTotal?: { total: number; asOf: string; source?: string } | null;
}

function formatMonth(month: string): string {
    const [year, m] = month.split("-").map(Number);
    return new Date(Date.UTC(year, m - 1, 1)).toLocaleDateString(undefined, { month: "short", year: "numeric", timeZone: "UTC" });
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
}

function formatDuration(ms: number): string {
    if (ms === 0) return "N/A";
    if (ms < 1000) return `${Math.round(ms)} ms`;
    return `${(ms / 1000).toFixed(1)} s`;
}

function formatNumber(n: number): string {
    return n.toLocaleString();
}

function formatPercent(share: number): string {
    const pct = share * 100;
    return `${pct.toFixed(pct >= 10 ? 0 : 1)}%`;
}

interface StatCardProps {
    label: string;
    value: string | number;
    subLabel?: string;
    subValue?: string | number;
}

const StatCard = ({ label, value, subLabel, subValue }: StatCardProps) => (
    <div className={style.statCard}>
        <div className={style.statValue}>{typeof value === "number" ? formatNumber(value) : value}</div>
        <div className={style.statLabel}>{label}</div>
        {subLabel && subValue !== undefined && (
            <div className={style.statSub}>
                {typeof subValue === "number" ? formatNumber(subValue) : subValue} {subLabel}
            </div>
        )}
    </div>
);

export const SystemStats = () => {
    const { data, isLoading, isError } = useQuery<SystemStatsData>({
        queryKey: ["system-stats"],
        queryFn: async () => {
            return (await (
                await API.get({
                    apiName: "auth",
                    path: "/getSystemStats",
                    options: {},
                }).response
            ).body.json()) as unknown as SystemStatsData;
        },
        staleTime: 5 * 60 * 1000,
    });

    if (isLoading) {
        return <SkeletonTable columns={3} rows={3} headers={["Metric", "Total", "Last 30 Days"]} />;
    }

    if (isError || !data) {
        return <p>Failed to load system statistics.</p>;
    }

    const adoption = data.snapshot?.adoption ?? null;
    const conformance = data.snapshot?.conformance ?? null;
    const remediation = data.snapshot?.remediation ?? null;
    const remediationPrev = data.snapshot?.remediationPreviousMonth ?? null;
    // The API returns null unless the total is positive; the check here is belt-and-braces
    const redTotal = data.redSitesTotal && data.redSitesTotal.total > 0 ? data.redSitesTotal : null;
    const chartRows = (data.monthly ?? []).map((row) => ({
        ...row,
        label: formatMonth(row.month) + (row.partial ? "*" : ""),
    }));

    return (
        <div className={style.SystemStats}>
            <section>
                <h3 className={style.sectionHeading}>Users &amp; Audits</h3>
                <div className={style.grid}>
                    <StatCard label="Total Users"  value={data.totalUsers}  subLabel="new in last 30 days" subValue={data.newUsers30d} />
                    <StatCard label="Active Users (30 days)" value={data.activeUsers30d} subLabel="units served" subValue={data.unitsServed30d} />
                    <StatCard label="Total Audits" value={data.totalAudits} subLabel="new in last 30 days" subValue={data.newAudits30d} />
                    <StatCard label="Audit Owners" value={data.auditOwners} subLabel="audits scanning now" subValue={data.activeAudits} />
                    <StatCard label="Total Pages Tracked" value={data.totalUrls} />
                </div>
            </section>

            <section>
                <h3 className={style.sectionHeading}>Adoption (Title II)</h3>
                {adoption ? (
                    <>
                        <div className={style.grid}>
                            <StatCard
                                label="UIC Red Sites Using Equalify"
                                value={redTotal ? `${formatNumber(adoption.redSitesTracked)} of ${formatNumber(redTotal.total)}` : adoption.redSitesTracked}
                                subLabel={redTotal ? "of live Red sites" : undefined}
                                subValue={redTotal ? formatPercent(adoption.redSitesTracked / redTotal.total) : undefined}
                            />
                            <StatCard label="Red Sites Scanned (30 days)" value={adoption.redSitesScanned30d} />
                            <StatCard label="Red Site Owners (Equalify accounts)" value={adoption.redSiteOwners} />
                            <StatCard label="uic.edu Hosts Tracked" value={adoption.uicHostsTracked} subLabel="hosts awaiting a theme check" subValue={adoption.redHostsPending} />
                        </div>
                        <p className={style.note}>
                            A site counts as Red when its homepage uses the uic-red WordPress theme; each host is rechecked weekly. Computed {formatDate(adoption.computedAt)}.
                            {redTotal && <> Red site total of {formatNumber(redTotal.total)} is as of {redTotal.asOf}{redTotal.source ? ` (${redTotal.source})` : ""}.</>}
                        </p>
                    </>
                ) : (
                    <p className={style.note}>Adoption figures appear after the first daily KPI run.</p>
                )}
            </section>

            <section>
                <h3 className={style.sectionHeading}>Accessibility Issues &amp; Conformance</h3>
                <div className={style.grid}>
                    <StatCard label="Total Blockers Found" value={data.totalBlockers} subLabel="new in last 30 days" subValue={data.newBlockers30d} />
                    {conformance && (
                        <>
                            <StatCard
                                label="Pages With No Open Blockers"
                                value={formatPercent(conformance.conformantShare)}
                                subLabel={`of ${formatNumber(conformance.monitoredPages)} pages tracked`}
                                subValue={formatNumber(conformance.conformantPages)}
                            />
                            <StatCard label="Open Blockers, WCAG Level A" value={conformance.openBlockersLevelA} />
                            <StatCard label="Open Blockers, WCAG Level AA" value={conformance.openBlockersLevelAA} />
                            <StatCard label="Open Blockers, AAA / Best Practice" value={conformance.openBlockersLevelAAA + conformance.openBlockersUntagged} />
                        </>
                    )}
                </div>
                {conformance ? (
                    <p className={style.note}>
                        Open blockers are those in each audit's latest scan that aren't ignored. A page counts as having no open blockers when none are recorded against it. Computed {formatDate(conformance.computedAt)}.
                    </p>
                ) : (
                    <p className={style.note}>Conformance figures appear after the first daily KPI run.</p>
                )}
            </section>

            <section>
                <h3 className={style.sectionHeading}>Remediation</h3>
                {remediation ? (
                    <>
                        <div className={style.grid}>
                            {remediationPrev && (
                                <>
                                    <StatCard label={`Issues Resolved (${formatMonth(remediationPrev.month)})`} value={remediationPrev.resolvedIssues} subLabel="audits rescanned" subValue={remediationPrev.auditsRescanned} />
                                    <StatCard label={`New Issues (${formatMonth(remediationPrev.month)})`} value={remediationPrev.newIssues} />
                                </>
                            )}
                            <StatCard label={`Issues Resolved (${formatMonth(remediation.month)} to date)`} value={remediation.resolvedIssues} subLabel="audits rescanned" subValue={remediation.auditsRescanned} />
                            <StatCard label={`New Issues (${formatMonth(remediation.month)} to date)`} value={remediation.newIssues} />
                        </div>
                        <p className={style.note}>
                            For each audit scanned in the month, its last scan of the month is compared with its last scan before the month began: an issue is resolved when it was there before and is gone now, and new when the reverse is true. Audits created during the month and scans that timed out are left out. Computed {formatDate(remediation.computedAt)}.
                        </p>
                    </>
                ) : (
                    <p className={style.note}>Remediation figures appear after the first daily KPI run.</p>
                )}
            </section>

            <section>
                <h3 className={style.sectionHeading}>Scans (Last 30 Days)</h3>
                <div className={style.grid}>
                    <StatCard label="Scan Runs" value={data.scanRuns30d} subLabel="failed" subValue={data.failedScans30d} />
                    <StatCard label="HTML Pages Scanned" value={data.htmlScans30d} />
                    <StatCard label="PDF Documents Scanned" value={data.pdfScans30d} />
                    <StatCard label="Avg HTML Scan Time" value={formatDuration(data.avgHtmlScanDurationMs30d)} />
                    <StatCard label="Avg PDF Scan Time" value={formatDuration(data.avgPdfScanDurationMs30d)} />
                </div>
            </section>

            {data.monthly && data.monthly.length > 0 && (
                <section>
                    <h3 className={style.sectionHeading}>Monthly KPIs (Last {data.monthly.length} Months)</h3>
                    <div className={style.charts}>
                        <figure className={style.chart}>
                            <figcaption className={style.chartTitle}>Sessions and active users</figcaption>
                            <ResponsiveContainer width="100%" height={220}>
                                <LineChart data={chartRows} margin={{ top: 8, right: 16, left: 0, bottom: 0 }}>
                                    <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} allowDecimals={false} />
                                    <Tooltip />
                                    <Legend />
                                    <Line type="monotone" dataKey="sessions" name="Sessions" stroke={themeVariables.red} strokeWidth={3} isAnimationActive={false} />
                                    <Line type="monotone" dataKey="activeUsers" name="Active users" stroke="currentColor" strokeWidth={3} isAnimationActive={false} />
                                </LineChart>
                            </ResponsiveContainer>
                        </figure>
                        <figure className={style.chart}>
                            <figcaption className={style.chartTitle}>Pages scanned</figcaption>
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart data={chartRows} margin={{ top: 8, right: 16, left: 0, bottom: 0 }}>
                                    <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} tickFormatter={(v: number) => (v >= 1000 ? `${Math.round(v / 1000)}k` : String(v))} />
                                    <Tooltip />
                                    <Legend />
                                    <Bar dataKey="htmlScans" name="HTML pages" stackId="pages" fill={themeVariables.red} isAnimationActive={false} />
                                    <Bar dataKey="pdfScans" name="PDF documents" stackId="pages" fill="currentColor" isAnimationActive={false} />
                                </BarChart>
                            </ResponsiveContainer>
                        </figure>
                    </div>
                    <div className={style.tableWrap}>
                        <table className="w-full border-collapse border border-gray-300" aria-label="Monthly KPIs">
                            <thead>
                                <tr className="bg-gray-100">
                                    {["Month", "Sessions", "Active Users", "Units Served", "Scan Runs", "HTML Pages", "PDF Pages", "New Users", "New Audits"].map((header) => (
                                        <th key={header} scope="col" className="border border-gray-300 px-4 py-2 text-left font-semibold">{header}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {data.monthly.map((row, index) => (
                                    <tr key={row.month} className={index % 2 === 0 ? "bg-white" : "bg-gray-50"}>
                                        <th scope="row" className="border border-gray-300 px-4 py-2 text-left font-normal whitespace-nowrap">
                                            {formatMonth(row.month)}{row.partial ? " (to date)" : ""}
                                        </th>
                                        {[row.sessions, row.activeUsers, row.unitsServed, row.scanRuns, row.htmlScans, row.pdfScans, row.newUsers, row.newAudits].map((value, i) => (
                                            <td key={i} className={`border border-gray-300 px-4 py-2 ${style.numeric}`}>{formatNumber(value)}</td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className={style.note}>
                        * current month to date. Sessions, Active Users, and Units Served are recorded from the release that added session tracking onward; earlier months show 0. Units Served counts distinct departments for SSO logins only.
                    </p>
                </section>
            )}

        </div>
    );
};
