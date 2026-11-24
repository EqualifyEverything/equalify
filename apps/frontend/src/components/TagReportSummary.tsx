import { useEffect, useId, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { Pie, PieChart, ResponsiveContainer } from "recharts";
import { FiExternalLink } from "react-icons/fi";
import { Card } from "./Card";
import { DataRow } from "./DataRow";
import { CardSpinner, PaginationControls } from "./BlockersTableSummary";
import { formatId, useGlobalStore } from "../utils";
import themeVariables from "../global-styles/variables.module.scss";
// Same cards and lists as the Audit page's summary
import style from "./BlockersTableSummary.module.scss";
import ownStyle from "./TagReportSummary.module.scss";

const PAGE_SIZE = 5;

export interface TagReportSummaryData {
  totalBlockersCount: number;
  latestScanBlockersCount: number;
  urlsWithBlockersCount: number;
  scannedUrlsCount: number;
  pdfBlockersCount: number;
  htmlBlockersCount: number;
}

interface PaginatedListResp<T> {
  items: T[];
  totalCount: number;
  page: number;
  pageSize: number;
}

interface UrlItem {
  key: string;
  count: number;
  auditId: string;
  auditName: string;
}

interface TagReportSummaryProps {
  tagId: string;
  summary: TagReportSummaryData;
  auditCount: number;
  /** Combined blockers at the start of the chart's date range */
  blockersAtStart: number | null;
  periodDays: number;
  isFetching: boolean;
}

const plural = (count: number, word: string) => `${count.toLocaleString()} ${word}${count === 1 ? "" : "s"}`;

// Paginated list fetcher for the URLs table below
const useTagReportList = <T,>(path: string, tagId: string, page: number) =>
  useQuery({
    queryKey: ["auditTags", path, tagId, page],
    queryFn: async () => {
      const response = await API.get({
        apiName: "auth",
        path,
        options: { queryParams: { id: tagId, page: page.toString(), pageSize: PAGE_SIZE.toString() } },
      }).response;
      return (await response.body.json()) as unknown as PaginatedListResp<T>;
    },
    placeholderData: (previousData) => previousData,
  });

// Stat cards plus "URLs with Most Blockers" for a Tag Report, combined across
// each tagged audit's latest scan
export const TagReportSummary = ({ tagId, summary, auditCount, blockersAtStart, periodDays, isFetching }: TagReportSummaryProps) => {
  const { darkMode, setAnnounceMessage } = useGlobalStore();
  const accentColor = darkMode ? themeVariables.yellow : themeVariables.red;
  const chartFillSecondary = darkMode ? themeVariables.dark_border : themeVariables.paper;
  // Fixed colors on the always-dark card, as on the Audit page (contrast)
  const increaseColor = themeVariables.yellow;
  const decreaseColor = themeVariables.green_light;

  const baseId = useId();
  const urlsHeadingId = `${baseId}-urls-heading`;

  const [urlsPage, setUrlsPage] = useState(0);
  useEffect(() => {
    setUrlsPage(0);
  }, [tagId]);

  const urls = useTagReportList<UrlItem>("/getTagReportUrls", tagId, urlsPage);
  const urlsTotalPages = urls.data ? Math.max(1, Math.ceil(urls.data.totalCount / urls.data.pageSize)) : 1;

  // The list can shrink while a later page is showing (an audit is removed
  // from the tag, or rescanned). The backend then answers with its last page:
  // follow it, so the pager counts from the page that is on screen. Only a
  // fresh answer counts, not one remembered from before a refetch.
  const servedUrlsPage = urls.data && !urls.isPlaceholderData && !urls.isFetching ? urls.data.page : null;
  useEffect(() => {
    if (servedUrlsPage !== null && servedUrlsPage !== urlsPage) setUrlsPage(servedUrlsPage);
  }, [servedUrlsPage, urlsPage]);

  // Announce page changes once the requested page has loaded, as on the Audit page
  const announcedUrlsPageRef = useRef(urlsPage);
  useEffect(() => {
    if (!urls.data) return;
    if (announcedUrlsPageRef.current !== urlsPage) {
      setAnnounceMessage(`Showing ${urls.data.items.length} of ${urls.data.totalCount} URLs, page ${urlsPage + 1} of ${urlsTotalPages}`, "normal", true);
    }
    announcedUrlsPageRef.current = urlsPage;
  }, [urls.data]);

  const delta = blockersAtStart === null ? null : summary.totalBlockersCount - blockersAtStart;
  const deltaText = delta === null
    ? null
    : delta > 0
      ? `${plural(delta, "more blocker")} than ${periodDays} days ago`
      : delta < 0
        ? `${plural(Math.abs(delta), "fewer blocker")} than ${periodDays} days ago`
        : `No change in the last ${periodDays} days`;

  const urlsPercent = summary.scannedUrlsCount > 0
    ? ((summary.urlsWithBlockersCount / summary.scannedUrlsCount) * 100).toFixed(1)
    : "0.0";
  const typedTotal = summary.pdfBlockersCount + summary.htmlBlockersCount;
  const typeBreakdownText = typedTotal > 0
    ? `${plural(summary.pdfBlockersCount, "PDF Blocker")} (${((summary.pdfBlockersCount / typedTotal) * 100).toFixed(1)}%) and ${plural(summary.htmlBlockersCount, "HTML Blocker")} (${((summary.htmlBlockersCount / typedTotal) * 100).toFixed(1)}%)`
    : null;

  // Detailed View link for one URL within one audit (same search format as
  // BlockersTableSummary's getUrlBlockersLinkSearch)
  const urlBlockersLink = (item: UrlItem) =>
    `/audits/${formatId(item.auditId)}?${new URLSearchParams({ view: "detailed", search: `"${item.key}"` }).toString()}`;

  return (
    <div className={style["BlockersTableSummary"]}>
      <div className="cards-50">
        <Card className="short">
          {isFetching && <CardSpinner />}
          <div className={style["blockers-count"] + " " + ownStyle.stat}>
            <h3><span className="font-extra-large">{summary.totalBlockersCount.toLocaleString()}</span> Blockers Found</h3>
            <div className={style["blockers-meta-group"]}>
              {deltaText && (
                <p
                  className={style["blockers-delta"]}
                  style={{ color: delta! > 0 ? increaseColor : delta! < 0 ? decreaseColor : undefined }}
                >
                  {deltaText}
                </p>
              )}
              <p className={style["blockers-meta"]}>Across <strong>{plural(auditCount, "audit")}</strong></p>
            </div>
          </div>
        </Card>
        <Card className="short" variant="light">
          {isFetching && <CardSpinner />}
          <div className={style["graph-card"] + " " + ownStyle.stat}>
            <div className={style["graph-card-text"]}>
              <h3>
                <span style={{ color: accentColor }}>{summary.urlsWithBlockersCount.toLocaleString()}</span> of {summary.scannedUrlsCount.toLocaleString()} URLs
                (<span style={{ color: accentColor }}>{urlsPercent}%</span>) in these audits have blockers.
              </h3>
              {typeBreakdownText && <p className={style["blocker-type-breakdown"]}>{typeBreakdownText}</p>}
            </div>
            <ResponsiveContainer className={style["donut-chart"] + " " + ownStyle.donut}>
              <PieChart {...{ "aria-hidden": "true" }}>
                <Pie
                  data={[
                    { name: "URLs with Blockers", value: summary.urlsWithBlockersCount, fill: accentColor },
                    { name: "URLs without Blockers", value: Math.max(0, summary.scannedUrlsCount - summary.urlsWithBlockersCount), fill: chartFillSecondary },
                  ]}
                  cx="50%"
                  cy="50%"
                  innerRadius="70%"
                  outerRadius="100%"
                  paddingAngle={0}
                  stroke="0"
                  dataKey="value"
                />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      <div>
        <Card variant="light">
          {urls.isFetching && !urls.isLoading && <CardSpinner />}
          <h3 id={urlsHeadingId}>URLs with Most Blockers</h3>
          {urls.error ? (
            <p>Couldn&apos;t load this list.</p>
          ) : !urls.data ? (
            <p>Loading…</p>
          ) : urls.data.totalCount === 0 ? (
            <p>No URLs with blockers found.</p>
          ) : (
            <>
              <div role="table" aria-labelledby={urlsHeadingId}>
                <DataRow asTableRow variant="highlight" the_value="Blockers" the_key="URL" />
                {urls.data.items.map((item) => (
                  <DataRow
                    asTableRow
                    key={item.auditId + item.key}
                    variant="tight"
                    the_key={
                      <>
                        <Link
                          to={urlBlockersLink(item)}
                          aria-label={`View ${item.count} blockers for ${item.key} in ${item.auditName}'s Detailed View`}
                        >
                          {item.key}
                        </Link>
                        <a
                          href={item.key}
                          target="_blank"
                          rel="noopener noreferrer"
                          aria-label={`Open ${item.key} in a new tab`}
                          className={style["external-link"]}
                        >
                          <FiExternalLink aria-hidden="true" focusable="false" />
                        </a>
                        <span className="font-small" style={{ display: "block" }}>
                          In <Link to={`/audits/${formatId(item.auditId)}`}>{item.auditName}</Link>
                        </span>
                      </>
                    }
                    the_value={item.count.toLocaleString()}
                  />
                ))}
              </div>
              <PaginationControls
                page={urlsPage}
                totalPages={urlsTotalPages}
                onPrevious={() => setUrlsPage((p) => Math.max(0, p - 1))}
                onNext={() => setUrlsPage((p) => Math.min(urlsTotalPages - 1, p + 1))}
                label="URLs with Most Blockers"
              />
            </>
          )}
        </Card>
      </div>
    </div>
  );
};
