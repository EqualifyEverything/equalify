import { db, event } from "#src/utils";
import { CloudWatchClient, GetMetricStatisticsCommand } from "@aws-sdk/client-cloudwatch";

const cloudwatch = new CloudWatchClient({ region: process.env.AWS_REGION ?? "us-east-2" });

const NAMESPACE = process.env.POWERTOOLS_METRICS_NAMESPACE ?? "equalifyuic";

// Months of month-over-month KPI history returned alongside the snapshot
// (the current, partial month plus the four full months before it).
// CloudWatch keeps hourly-resolution datapoints for 15 months, so anything
// up to 12 is safe if this is raised later.
const MONTHS_OF_HISTORY = 5;

interface MonthWindow { month: string; start: Date; end: Date }

// Whole UTC calendar months, oldest first, ending with the current (partial) month.
// Whole months keep the CloudWatch period a multiple of 3600, which is required
// for any query whose start is more than 63 days in the past.
function monthWindows(count: number): MonthWindow[] {
    const now = new Date();
    const windows: MonthWindow[] = [];
    for (let i = count - 1; i >= 0; i--) {
        const start = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth() - i, 1));
        const end = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 1, 1));
        windows.push({ month: start.toISOString().slice(0, 7), start, end });
    }
    return windows;
}

async function getCwMetric(
    serviceName: string,
    metricName: string,
    stat: "Sum" | "Average",
    startTime: Date,
    endTime: Date,
): Promise<number> {
    const res = await cloudwatch.send(new GetMetricStatisticsCommand({
        Namespace: NAMESPACE,
        MetricName: metricName,
        Dimensions: [{ Name: "service", Value: serviceName }],
        StartTime: startTime,
        EndTime: endTime,
        // Single bucket spanning the whole window — we want one aggregate value, not a time series
        Period: Math.round((endTime.getTime() - startTime.getTime()) / 1000),
        Statistics: [stat],
    }));
    const point = res.Datapoints?.[0];
    if (!point) return 0;
    return stat === "Sum" ? (point.Sum ?? 0) : (point.Average ?? 0);
}

export const getSystemStats = async () => {
    await db.connect();

    const userRow = (await db.query({
        text: `SELECT type FROM users WHERE id = $1`,
        values: [event.claims.sub],
    })).rows[0];

    if (userRow?.type !== "admin") {
        await db.clean();
        return { statusCode: 403, body: JSON.stringify({ error: "Forbidden" }) };
    }

    const now = new Date();
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const windows = monthWindows(MONTHS_OF_HISTORY);

    // All DB counts in two round trips, all CloudWatch calls in parallel
    const [dbResult, monthlyResult, htmlScans, pdfScans, htmlAvgDuration, pdfAvgDuration, monthlyHtml, monthlyPdf] = await Promise.all([
        db.query({
            text: `
                SELECT
                    (SELECT COUNT(*) FROM users)                                                        AS total_users,
                    (SELECT COUNT(*) FROM users   WHERE created_at >= NOW() - INTERVAL '30 days')      AS new_users_30d,
                    (SELECT COUNT(*) FROM audits)                                                       AS total_audits,
                    (SELECT COUNT(*) FROM audits  WHERE created_at >= NOW() - INTERVAL '30 days')      AS new_audits_30d,
                    (SELECT COUNT(*) FROM urls)                                                         AS total_urls,
                    (SELECT COUNT(*) FROM blockers)                                                     AS total_blockers,
                    (SELECT COUNT(*) FROM blockers WHERE created_at >= NOW() - INTERVAL '30 days')     AS new_blockers_30d,
                    (SELECT COUNT(*) FROM audits  WHERE status = 'processing')                          AS active_audits,
                    (SELECT COUNT(*) FROM scans   WHERE status = 'failed'
                                                    AND created_at >= NOW() - INTERVAL '30 days')      AS failed_scans_30d
            `,
        }),
        // Month-over-month KPIs. Sessions (and therefore active users / units served)
        // only exist from the release that added the sessions table onward.
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
                    (SELECT COUNT(*)                   FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month') AS sessions,
                    (SELECT COUNT(DISTINCT s.user_id)  FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month') AS active_users,
                    (SELECT COUNT(DISTINCT s.department) FROM sessions s WHERE s.created_at >= m AND s.created_at < m + INTERVAL '1 month'
                                                                           AND s.department IS NOT NULL)                                AS units_served,
                    (SELECT COUNT(*) FROM scans  sc WHERE sc.created_at >= m AND sc.created_at < m + INTERVAL '1 month')                AS scan_runs,
                    (SELECT COUNT(*) FROM users  u  WHERE u.created_at  >= m AND u.created_at  < m + INTERVAL '1 month')                AS new_users,
                    (SELECT COUNT(*) FROM audits a  WHERE a.created_at  >= m AND a.created_at  < m + INTERVAL '1 month')                AS new_audits
                FROM months
                ORDER BY m
            `,
            values: [MONTHS_OF_HISTORY],
        }),
        getCwMetric("aws-lambda-scan-html", "scansStarted", "Sum",     thirtyDaysAgo, now),
        getCwMetric("aws-lambda-scan-pdf",  "scansStarted", "Sum",     thirtyDaysAgo, now),
        getCwMetric("aws-lambda-scan-html", "ScanDuration", "Average", thirtyDaysAgo, now),
        getCwMetric("aws-lambda-scan-pdf",  "ScanDuration", "Average", thirtyDaysAgo, now),
        Promise.all(windows.map(w => getCwMetric("aws-lambda-scan-html", "scansStarted", "Sum", w.start, w.end))),
        Promise.all(windows.map(w => getCwMetric("aws-lambda-scan-pdf",  "scansStarted", "Sum", w.start, w.end))),
    ]);

    await db.clean();

    const monthlyByMonth = new Map<string, any>(monthlyResult.rows.map((r: any) => [r.month, r]));
    const monthly = windows.map((w, i) => {
        const row = monthlyByMonth.get(w.month) ?? {};
        return {
            month:       w.month,
            sessions:    parseInt(row.sessions     ?? "0"),
            activeUsers: parseInt(row.active_users ?? "0"),
            unitsServed: parseInt(row.units_served ?? "0"),
            scanRuns:    parseInt(row.scan_runs    ?? "0"),
            htmlScans:   monthlyHtml[i],
            pdfScans:    monthlyPdf[i],
            newUsers:    parseInt(row.new_users    ?? "0"),
            newAudits:   parseInt(row.new_audits   ?? "0"),
        };
    });

    const row = dbResult.rows[0];
    return {
        totalUsers:              parseInt(row.total_users),
        newUsers30d:             parseInt(row.new_users_30d),
        totalAudits:             parseInt(row.total_audits),
        newAudits30d:            parseInt(row.new_audits_30d),
        totalUrls:               parseInt(row.total_urls),
        totalBlockers:           parseInt(row.total_blockers),
        newBlockers30d:          parseInt(row.new_blockers_30d),
        activeAudits:            parseInt(row.active_audits),
        failedScans30d:          parseInt(row.failed_scans_30d),
        htmlScans30d:            htmlScans,
        pdfScans30d:             pdfScans,
        avgHtmlScanDurationMs30d: htmlAvgDuration,
        avgPdfScanDurationMs30d:  pdfAvgDuration,
        monthly,
    };
};
