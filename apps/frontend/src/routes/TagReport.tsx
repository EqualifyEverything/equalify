import { useEffect, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { TbAlertTriangle, TbHelpCircleFilled } from "react-icons/tb";
import { FaTrash } from "react-icons/fa";
import { Card } from "#src/components/Card.tsx";
import { ConfirmDialog } from "#src/components/ConfirmDialog.tsx";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { TagReportAddAudits } from "#src/components/TagReportAddAudits.tsx";
import { SkeletonAuditHeader } from "#src/components/Skeleton.tsx";
import { TagReportChart, TagReportChartData } from "#src/components/TagReportChart.tsx";
import { TagReportSummary, TagReportSummaryData } from "#src/components/TagReportSummary.tsx";
import { formatId, useGlobalStore } from "../utils";
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

// Highest contributors first; audits without a completed scan last
const sortAudits = (audits: TagReportAudit[]) =>
  [...audits].sort((a, b) => (b.blockerCount ?? -1) - (a.blockerCount ?? -1));

const scanStatusNote = (audit: TagReportAudit) => {
  if (audit.latestScanStatus === "processing") return "Scanning now";
  if (audit.latestScanStatus === "failed") return "Last scan failed";
  return null;
};

// Roll-up of every audit with a tag: the audits themselves (which can be
// added and removed here), combined blockers over time, and combined stats
export const TagReport = () => {
  const { tagId } = useParams();
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();
  const [days, setDays] = useState(90);
  const [scrollFadeRef, showScrollFade] = useScrollFade();
  const [pendingRemove, setPendingRemove] = useState<TagReportAudit | null>(null);
  const [isRemoving, setIsRemoving] = useState(false);
  // After a removal the clicked button is gone, so focus moves to the next
  // row's Remove button (or onward) once the refreshed table renders
  const focusAfterRemoveRef = useRef<string | null>(null);

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

  useEffect(() => {
    const nextAuditId = focusAfterRemoveRef.current;
    if (nextAuditId === null) return;
    focusAfterRemoveRef.current = null;
    requestAnimationFrame(() => {
      const target =
        (nextAuditId && document.querySelector<HTMLElement>(`[data-remove-audit="${nextAuditId}"]`)) ||
        document.getElementById("tag-report-audits-heading") ||
        document.getElementById("tagReportAddAudits");
      target?.focus();
    });
  }, [report.data]);

  const removeAudit = async () => {
    if (!pendingRemove || !report.data) return;
    const audit = pendingRemove;
    const rows = sortAudits(report.data.audits);
    const index = rows.findIndex((row) => row.id === audit.id);
    const nextRow = rows[index + 1] ?? rows[index - 1];
    setIsRemoving(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/updateTagAudits",
          options: { body: { tagId: tagId!, remove: [audit.id] } },
        }).response
      ).body.json()) as unknown as { status: string; message?: string };
      if (response?.status !== "success") {
        setAnnounceMessage(response?.message ?? "Unable to remove audit.", "error");
        return;
      }
      // "" = no rows left; the effect above falls back to the heading or picker
      focusAfterRemoveRef.current = nextRow?.id ?? "";
      setPendingRemove(null);
      await queryClient.invalidateQueries({ queryKey: ["auditTags"] });
      queryClient.invalidateQueries({ queryKey: ["audits"] });
      queryClient.invalidateQueries({ queryKey: ["audit"] });
      setAnnounceMessage(`Removed ${audit.name} from ${report.data.tag.name}.`, "success");
    } catch (err) {
      console.error("Failed to remove audit from tag:", err);
      setAnnounceMessage("Unable to remove audit.", "error");
    } finally {
      setIsRemoving(false);
    }
  };

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
  const sortedAudits = sortAudits(audits);
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
            <p>Add audits below, or add the "{tag.name}" tag from an audit's page or the Audit Builder, and their results will appear here.</p>
            <TagReportAddAudits tagId={tag.id} tagName={tag.name} taggedAuditIds={[]} />
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

          <Card variant="light">
            <h2 id="tag-report-audits-heading" tabIndex={-1}>Audits</h2>
            <TagReportAddAudits tagId={tag.id} tagName={tag.name} taggedAuditIds={audits.map((audit) => audit.id)} />
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
                      <th scope="col">Actions</th>
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
                          <td>
                            <StyledButton
                              label={`Remove ${audit.name} from ${tag.name}`}
                              icon={<FaTrash />}
                              showLabel={false}
                              onClick={() => setPendingRemove(audit)}
                              data-remove-audit={audit.id}
                            />
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          </Card>

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

        </>
      )}
      {report.isFetching && !report.isLoading && <span className="sr-only" role="status">Updating report</span>}
      <ConfirmDialog
        open={pendingRemove !== null}
        onOpenChange={(open) => { if (!open) setPendingRemove(null); }}
        title="Remove audit from tag"
        description={<>Remove <strong>"{pendingRemove?.name}"</strong> from <strong>"{tag.name}"</strong>? Its scans will no longer be counted in this report, including past totals. The audit itself isn't affected.</>}
        confirmLabel="Remove"
        confirmVariant="red"
        loading={isRemoving}
        onConfirm={removeAudit}
        onCloseAutoFocus={(e) => {
          // After a removal, the effect above moves focus; on cancel, Radix
          // returns it to the row's Remove button as usual
          if (focusAfterRemoveRef.current !== null) e.preventDefault();
        }}
      />
    </div>
  );
};
