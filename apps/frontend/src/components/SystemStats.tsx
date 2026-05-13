import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { SkeletonTable } from "./Skeleton";
import style from "./SystemStats.module.scss";

interface SystemStatsData {
    totalUsers: number;
    newUsers30d: number;
    totalAudits: number;
    newAudits30d: number;
    totalUrls: number;
    totalBlockers: number;
    newBlockers30d: number;
    activeAudits: number;
    failedScans30d: number;
    htmlScans30d: number;
    pdfScans30d: number;
    avgHtmlScanDurationMs30d: number;
    avgPdfScanDurationMs30d: number;
}

function formatDuration(ms: number): string {
    if (ms === 0) return "N/A";
    if (ms < 1000) return `${Math.round(ms)} ms`;
    return `${(ms / 1000).toFixed(1)} s`;
}

function formatNumber(n: number): string {
    return n.toLocaleString();
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

    return (
        <div className={style.SystemStats}>
            <section>
                <h3 className={style.sectionHeading}>Users &amp; Audits</h3>
                <div className={style.grid}>
                    <StatCard label="Total Users"  value={data.totalUsers}  subLabel="new in last 30 days" subValue={data.newUsers30d} />
                    <StatCard label="Total Audits" value={data.totalAudits} subLabel="new in last 30 days" subValue={data.newAudits30d} />
                    <StatCard label="Active Audits" value={data.activeAudits} />
                    <StatCard label="Total Pages Tracked" value={data.totalUrls} />
                </div>
            </section>

            <section>
                <h3 className={style.sectionHeading}>Accessibility Issues</h3>
                <div className={style.grid}>
                    <StatCard label="Total Blockers Found" value={data.totalBlockers} subLabel="new in last 30 days" subValue={data.newBlockers30d} />
                </div>
            </section>

            <section>
                <h3 className={style.sectionHeading}>Scans (Last 30 Days)</h3>
                <div className={style.grid}>
                    <StatCard label="HTML Scans Completed" value={data.htmlScans30d} />
                    <StatCard label="PDF Scans Completed"  value={data.pdfScans30d} />{/* 
                    <StatCard label="Failed Scans"         value={data.failedScans30d} /> */}
                    <StatCard label="Avg HTML Scan Time"   value={formatDuration(data.avgHtmlScanDurationMs30d)} />
                    <StatCard label="Avg PDF Scan Time"    value={formatDuration(data.avgPdfScanDurationMs30d)} />
                </div>
            </section>

        </div>
    );
};
