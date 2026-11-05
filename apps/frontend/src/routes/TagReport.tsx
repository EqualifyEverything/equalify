import { useState } from "react";
import { Link, useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { TbAlertTriangle, TbHelpCircleFilled } from "react-icons/tb";
import { Card } from "#src/components/Card.tsx";
import { SkeletonAuditHeader } from "#src/components/Skeleton.tsx";
import { TagReportChart, TagReportChartData } from "#src/components/TagReportChart.tsx";
import { TagReportSummary, TagReportSummaryData } from "#src/components/TagReportSummary.tsx";
import { formatId } from "../utils";
import { useScrollFade } from "#src/utils/useScrollFade.ts";
import styles from "./TagReport.module.scss";

interface TagReportAudit {
  id: string;
  name: string;
  interval: string;
  createdAt: string;
  user: { name: string | null; email: string | null } | null;
  urlCount: number;
  blockerCount: number | null; // latest complete scan
  blockersAtStart: number | null; // as of the chart range's first day
  lastCompleteScanAt: string | null;
  latestScanStatus: string | null;
  latestScanAt: string | null;
}

interface TagReportResp {
  tag: { id: string; name: string };
  periodDays: number;
  audits: TagReportAudit[];
  summary: TagReportSummaryData;
}

const fetchTagReport = async <T,>(path: string, tagId: string, days: number) => {
  const response = await API.get({
    apiName: "auth",
    path,
    options: { queryParams: { id: tagId, days: days.toString() } },
  }).response;
  return (await response.body.json()) as unknown as T;
};

const shortDate = (value: string) =>
  new Date(value).toLocaleDateString("en-US", { year: "numeric", month: "numeric", day: "numeric" });

const plural = (count: number, word: string) => `${count.toLocaleString()} ${word}${count === 1 ? "" : "s"}`;

// Change over the selected range, in words rather than color alone
const ChangeCell = ({ audit, periodDays }: { audit: TagReportAudit; periodDays: number }) => {
  if (audit.blockerCount === null) return <>—</>;
  if (audit.blockersAtStart === null) {
    return <span title={`No scan before the last ${periodDays} days`}>New<span className="sr-only">: no scan before the last {periodDays} days</span></span>;
  }
  const delta = audit.blockerCount - audit.blockersAtStart;
  if (delta === 0) return <>No change</>;
  return <>{delta > 0 ? `${delta.toLocaleString()} more` : `${Math.abs(delta).toLocaleString()} fewer`}</>;
};

const scanStatusNote = (audit: TagReportAudit) => {
  if (audit.latestScanStatus === "processing") return "Scanning now";
  if (audit.latestScanStatus === "failed") return "Last scan failed";
  return null;
};

// Read-only roll-up of every audit with a tag: combined blockers over time,
// combined stats, and the audits themselves
export const TagReport = () => {
  const { tagId } = useParams();
  const [days, setDays] = useState(90);
  const [scrollFadeRef, showScrollFade] = useScrollFade();

  const report = useQuery({
    queryKey: ["auditTags", "report", tagId, days],
    queryFn: () => fetchTagReport<TagReportResp>("/getTagReport", tagId!, days),
    placeholderData: (previousData) => previousData,
  });
  const chart = useQuery({
    queryKey: ["auditTags", "reportChart", tagId, days],
    queryFn: () => fetchTagReport<TagReportChartData>("/getTagReportChart", tagId!, days),
    placeholderData: (previousData) => previousData,
  });

  if (report.isError) {
    return (
      <div className={styles.TagReport}>
        <h1 className="initial-focus-element">Tag Report</h1>
        <Card variant="short-error">
          <TbAlertTriangle className="icon-small" />
          <div className="font-small">
            <b>Unable to load this tag report.</b> The tag may have been renamed, merged, or deleted. <Link to="/audits">Back to Audits</Link>
          </div>
        </Card>
      </div>
    );
  }

  if (!report.data) {
    return <div className={styles.TagReport}><SkeletonAuditHeader /></div>;
  }

  const { tag, audits, summary, periodDays } = report.data;
  // Highest contributors first
  const sortedAudits = [...audits].sort((a, b) => (b.blockerCount ?? -1) - (a.blockerCount ?? -1));
  const scanning = audits.filter((audit) => audit.latestScanStatus === "processing").length;
  const failed = audits.filter((audit) => audit.latestScanStatus === "failed").length;
  const neverScanned = audits.filter((audit) => audit.lastCompleteScanAt === null).length;
  const blockersAtStart = chart.data?.data[0]?.blockers ?? null;

  return (
    <div className={styles.TagReport}>
      <div className={styles.header}>
        <h1 className="initial-focus-element">
          <span className={styles.label}>Tag Report</span> {tag.name}
        </h1>
        <p className="font-small">
          Combined results for the {plural(audits.length, "audit")} tagged "{tag.name}".
          History reflects the audits that have this tag today, so adding or removing an audit changes past totals too.
        </p>
      </div>

      {audits.length === 0 ? (
        <Card variant="empty-state-card">
          <TbHelpCircleFilled />
          <div className="text">
            <h2>No audits have this tag yet.</h2>
            <p>Add the "{tag.name}" tag to an audit from the audit's page or the Audit Builder, and its results will appear here.</p>
          </div>
        </Card>
      ) : (
        <>
          {(scanning > 0 || failed > 0 || neverScanned > 0) && (
            <Card variant="light" className={styles.notice}>
              <p className="font-small">
                {[
                  scanning > 0 && `${plural(scanning, "audit")} ${scanning === 1 ? "is" : "are"} scanning now`,
                  failed > 0 && `${plural(failed, "audit")}'s last scan failed`,
                  neverScanned > 0 && `${plural(neverScanned, "audit")} ${neverScanned === 1 ? "hasn't" : "haven't"} completed a scan yet`,
                ].filter(Boolean).join("; ")}.
                {" "}Totals and the chart use each audit's most recent completed scan.
              </p>
            </Card>
          )}

          <TagReportChart
            chartData={chart.data}
            isLoading={chart.isLoading}
            isFetching={chart.isFetching && !chart.isLoading}
            days={days}
            onDaysChange={setDays}
          />

          <TagReportSummary
            tagId={tagId!}
            summary={summary}
            auditCount={audits.length}
            blockersAtStart={blockersAtStart}
            periodDays={periodDays}
            isFetching={report.isFetching}
          />

          <Card variant="light">
            <h2 id="tag-report-audits-heading">Audits</h2>
            <div className="table-container">
              <div
                className={"table-scroll-wrapper" + (showScrollFade ? " scroll-fade-active" : "")}
                ref={scrollFadeRef}
                tabIndex={0}
                role="region"
                aria-label="Tagged audits table, scrollable horizontally"
              >
                <table aria-labelledby="tag-report-audits-heading">
                  <thead>
                    <tr>
                      <th scope="col">Name</th>
                      <th scope="col">Blockers</th>
                      <th scope="col">Change ({periodDays} days)</th>
                      <th scope="col">Last Completed Scan</th>
                      <th scope="col">URLs</th>
                      <th scope="col">Runs</th>
                      <th scope="col">Created By</th>
                    </tr>
                  </thead>
                  <tbody>
                    {sortedAudits.map((audit) => {
                      const note = scanStatusNote(audit);
                      return (
                        <tr key={audit.id}>
                          <td><Link to={`/audits/${formatId(audit.id)}`}>{audit.name}</Link></td>
                          <td>{audit.blockerCount === null ? "—" : <b>{audit.blockerCount.toLocaleString()}</b>}</td>
                          <td><ChangeCell audit={audit} periodDays={periodDays} /></td>
                          <td>
                            {audit.lastCompleteScanAt ? shortDate(audit.lastCompleteScanAt) : "Not scanned yet"}
                            {note && <span className={styles["scan-note"]}>{note}</span>}
                          </td>
                          <td>{audit.urlCount.toLocaleString()}</td>
                          <td>{audit.interval}</td>
                          <td>{audit.user?.name ?? audit.user?.email ?? "—"}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          </Card>
        </>
      )}
      {report.isFetching && !report.isLoading && <span className="sr-only" role="status">Updating report</span>}
    </div>
  );
};
