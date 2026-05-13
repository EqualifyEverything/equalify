import { db, event } from "#src/utils";
import { CloudWatchClient, GetMetricStatisticsCommand } from "@aws-sdk/client-cloudwatch";

const cloudwatch = new CloudWatchClient({ region: process.env.AWS_REGION ?? "us-east-2" });

const NAMESPACE = "equalifyuic";

async function getCwMetric(
    serviceName: string,
    metricName: string,
    stat: "Sum" | "Average",
): Promise<number> {
    const endTime = new Date();
    const startTime = new Date(endTime.getTime() - 30 * 24 * 60 * 60 * 1000);
    const res = await cloudwatch.send(new GetMetricStatisticsCommand({
        Namespace: NAMESPACE,
        MetricName: metricName,
        Dimensions: [{ Name: "service", Value: serviceName }],
        StartTime: startTime,
        EndTime: endTime,
        // Single 30-day bucket — we want one aggregate value, not a time series
        Period: 30 * 24 * 60 * 60,
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

    // All DB counts in one round trip, all CloudWatch calls in parallel
    const [dbResult, htmlScans, pdfScans, htmlAvgDuration, pdfAvgDuration] = await Promise.all([
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
        getCwMetric("aws-lambda-scan-html", "scansStarted",  "Sum"),
        getCwMetric("aws-lambda-scan-pdf",  "scansStarted",  "Sum"),
        getCwMetric("aws-lambda-scan-html", "ScanDuration",  "Average"),
        getCwMetric("aws-lambda-scan-pdf",  "ScanDuration",  "Average"),
    ]);

    await db.clean();

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
    };
};
