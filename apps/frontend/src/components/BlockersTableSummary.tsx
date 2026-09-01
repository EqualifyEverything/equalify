import { useQuery } from "@tanstack/react-query";
import { ChangeEvent, useEffect, useId, useRef, useState } from "react";
import style from "./BlockersTableSummary.module.scss";
import * as API from "aws-amplify/api";
import { DataRow } from "./DataRow";
import { Card } from "./Card";
import { StyledButton } from "./StyledButton";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { Page } from "#src/routes/Audit.tsx";
import { Pie, PieChart, ResponsiveContainer } from "recharts";
import { GrPowerCycle } from "react-icons/gr";
import { FiExternalLink } from "react-icons/fi";
import { Link, useSearchParams } from "react-router-dom";

import themeVariables from "../global-styles/variables.module.scss";
import { SkeletonAuditHeader } from "./Skeleton";
import { useGlobalStore } from "#src/utils";
//const apiClient = API.generateClient();

const MOST_COMMON_PAGE_SIZE = 5;

interface PaginatedListResp<T> {
  items: T[];
  totalCount: number;
  page: number;
  pageSize: number;
}

const CardSpinner = () => (
  <span className={style["card-spinner"]} role="img" aria-label="Refreshing">
    <GrPowerCycle className={style.spinning} />
  </span>
);

const PaginationControls = ({
  page,
  totalPages,
  onPrevious,
  onNext,
  label,
}: {
  page: number;
  totalPages: number;
  onPrevious: () => void;
  onNext: () => void;
  label: string;
}) => (
  <div className={style["pagination"]} role="navigation" aria-label={`${label} pagination`}>
    <span className={style["pagination-text"]}>Page {page + 1} of {totalPages}</span>
    <div className={style["pagination-buttons"]}>
      <StyledButton onClick={onPrevious} disabled={page <= 0} label="Previous" variant="light" />
      <StyledButton onClick={onNext} disabled={page >= totalPages - 1} label="Next" variant="light" />
    </div>
  </div>
);

interface BlockersTableSummaryProps {
  auditId: string;
  isShared: boolean;
  chartData: any;
  pages: Page[];
  scans: any;
}

interface SummaryRespCountItem {
  "count": number,
  "key": string,
  "category"?: string | null
}

interface SummaryRespScan {
  "blockerCount": number,
  "pagesCount": number
}

interface SummaryResp {
  "urlsWithBlockersCount": number,
  "mostCommonCategory": SummaryRespCountItem[],
  "mostCommonTags": SummaryRespCountItem[],
  "latestScan": SummaryRespScan | null,
  "previousScan": SummaryRespScan | null,
  "pdfBlockersCount": number,
  "htmlBlockersCount": number,
  "totalBlockersCount": number,
  "uniqueBlockersCount": number
}

export const BlockersTableSummary = ({ auditId, isShared, chartData, pages, scans }: BlockersTableSummaryProps) => {

  const { darkMode, setAnnounceMessage } = useGlobalStore();
  const accentColor = darkMode ? themeVariables.yellow : themeVariables.red;
  const chartFillSecondary = darkMode ? themeVariables.dark_border : themeVariables.paper;
  // This card is always rendered with the "dark" Card variant (near-black
  // background in both light and dark app themes), so its accent colors are
  // fixed rather than following the app-wide darkMode toggle — red and the
  // default green both fall below 4.5:1 contrast against that background.
  const increaseColor = themeVariables.yellow;
  const decreaseColor = themeVariables.green_light;
  // The "Blockers per URL" card uses the "light" Card variant, which (unlike
  // the card above) does flip background with the app theme, so its accent
  // colors follow darkMode like accentColor does. Plain green fails 4.5:1 on
  // the dark-theme background, and green_light fails it on the light-theme
  // (white) background, so each needs its own theme-appropriate shade.
  const decreaseColorOnLightVariant = darkMode ? themeVariables.green_light : themeVariables.green_dark;

  const [searchParams] = useSearchParams();

  const getUrlBlockersLinkSearch = (url: string) => {
    const params = new URLSearchParams(searchParams);
    params.set("view", "detailed");
    // Quoted so the Detailed View search matches this URL exactly, rather than
    // also matching URLs that merely contain it as a substring (e.g. sub-pages).
    params.set("search", `"${url}"`);
    params.delete("page");
    return params.toString();
  };

  const getCategoryBlockersLinkSearch = (category: string) => {
    const params = new URLSearchParams(searchParams);
    params.set("view", "detailed");
    params.set("categories", category);
    params.delete("page");
    return params.toString();
  };

  const baseId = useId();
  const urlsHeadingId = `${baseId}-urls-heading`;
  const blockersHeadingId = `${baseId}-blockers-heading`;

  const [mostCommonCategoriesLimit, setMostCommonCategoriesLimit] = useState(3);
  const [mostCommonTagsLimit, setMostCommonTagsLimit] = useState(3);

  const { data, isLoading, isFetching, error } = useQuery({
    queryKey: [
      "auditSummary", auditId,
      mostCommonCategoriesLimit, mostCommonTagsLimit
    ],
    queryFn: async () => {
      const params: Record<string, string> = {
        id: auditId,
        mostCommonCategoriesLimit: mostCommonCategoriesLimit.toString(),
        mostCommonTagsLimit: mostCommonTagsLimit.toString()
      };
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getAuditSummaryFast",
        options: { queryParams: params },
      }).response;
      const resp = (await response.body.json()) as any as SummaryResp;
      //console.log(resp);
      return resp;
    }
  });

  const [urlsPage, setUrlsPage] = useState(0);
  const [blockersPage, setBlockersPage] = useState(0);

  // Duplicate handling for the two "Most Common" lists: "all" counts every
  // occurrence, "group" counts unique blockers (by content hash), "hide"
  // only counts blockers that appear exactly once.
  const [duplicatesMode, setDuplicatesMode] = useState<string>("all");

  const handleDuplicatesModeChange = (mode: string) => {
    setDuplicatesMode(mode);
    setUrlsPage(0);
    setBlockersPage(0);
    setAnnounceMessage(
      mode === "group"
        ? "Counting one per unique blocker"
        : mode === "hide"
          ? "Hiding blockers that have duplicates"
          : "Counting every blocker occurrence"
    );
  };

  // A new audit means these page numbers are stale — start back at page 1
  // rather than requesting, say, page 4 of a brand-new audit's short list.
  useEffect(() => {
    setUrlsPage(0);
    setBlockersPage(0);
  }, [auditId]);

  const {
    data: mostCommonUrlsData,
    isLoading: isMostCommonUrlsLoading,
    isFetching: isMostCommonUrlsFetching,
    error: mostCommonUrlsError,
  } = useQuery({
    queryKey: ["mostCommonUrls", auditId, urlsPage, duplicatesMode],
    queryFn: async () => {
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getMostCommonUrls",
        options: {
          queryParams: {
            id: auditId,
            page: urlsPage.toString(),
            pageSize: MOST_COMMON_PAGE_SIZE.toString(),
            ...(duplicatesMode !== "all" && { duplicates: duplicatesMode }),
          },
        },
      }).response;
      return (await response.body.json()) as any as PaginatedListResp<SummaryRespCountItem>;
    },
    placeholderData: (previousData) => previousData,
  });
  const urlsTotalPages = mostCommonUrlsData
    ? Math.max(1, Math.ceil(mostCommonUrlsData.totalCount / mostCommonUrlsData.pageSize))
    : 1;

  const {
    data: mostCommonBlockersData,
    isLoading: isMostCommonBlockersLoading,
    isFetching: isMostCommonBlockersFetching,
    error: mostCommonBlockersError,
  } = useQuery({
    queryKey: ["mostCommonBlockers", auditId, blockersPage, duplicatesMode],
    queryFn: async () => {
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getMostCommonBlockers",
        options: {
          queryParams: {
            id: auditId,
            page: blockersPage.toString(),
            pageSize: MOST_COMMON_PAGE_SIZE.toString(),
            ...(duplicatesMode !== "all" && { duplicates: duplicatesMode }),
          },
        },
      }).response;
      return (await response.body.json()) as any as PaginatedListResp<SummaryRespCountItem>;
    },
    placeholderData: (previousData) => previousData,
  });
  const blockersTotalPages = mostCommonBlockersData
    ? Math.max(1, Math.ceil(mostCommonBlockersData.totalCount / mostCommonBlockersData.pageSize))
    : 1;

  // Announce pagination changes to screen readers once the newly requested
  // page has actually loaded (data still holds the previous page until then).
  const announcedUrlsPageRef = useRef(urlsPage);
  useEffect(() => {
    if (!mostCommonUrlsData) return;
    if (announcedUrlsPageRef.current !== urlsPage) {
      setAnnounceMessage(
        `Showing ${mostCommonUrlsData.items.length} of ${mostCommonUrlsData.totalCount} URLs, page ${urlsPage + 1} of ${urlsTotalPages}`,
        "normal",
        true
      );
    }
    announcedUrlsPageRef.current = urlsPage;
  }, [mostCommonUrlsData]);

  const announcedBlockersPageRef = useRef(blockersPage);
  useEffect(() => {
    if (!mostCommonBlockersData) return;
    if (announcedBlockersPageRef.current !== blockersPage) {
      setAnnounceMessage(
        `Showing ${mostCommonBlockersData.items.length} of ${mostCommonBlockersData.totalCount} blockers, page ${blockersPage + 1} of ${blockersTotalPages}`,
        "normal",
        true
      );
    }
    announcedBlockersPageRef.current = blockersPage;
  }, [mostCommonBlockersData]);

  function daysSince(date: Date) {
    const timeDifferenceInMs = new Date().getTime() - date.getTime();
    const msPerDay = 1000 * 60 * 60 * 24;
    const daysDifference = Math.round(timeDifferenceInMs / msPerDay);
    return daysDifference;
  }

  // Only scans that have finished have a frozen blocker_count, so the delta
  // compares the two most recent completed scans rather than raw array position.
  const completedScans = scans.filter(
    (scan: any) => scan.status === "complete" && scan.blocker_count !== null && scan.blocker_count !== undefined
  );
  const latestCompletedScan = completedScans[completedScans.length - 1];
  const previousCompletedScan = completedScans[completedScans.length - 2];
  const blockersDelta = latestCompletedScan && previousCompletedScan
    ? latestCompletedScan.blocker_count - previousCompletedScan.blocker_count
    : null;

  const blockersDeltaText = blockersDelta === null
    ? null
    : blockersDelta > 0
      ? `${blockersDelta.toLocaleString()} more blocker${blockersDelta === 1 ? "" : "s"} since last scan`
      : blockersDelta < 0
        ? `${Math.abs(blockersDelta).toLocaleString()} fewer blocker${Math.abs(blockersDelta) === 1 ? "" : "s"} since last scan`
        : "No change since last scan";

  const daysSinceLastScan = scans.length > 0 ? daysSince(new Date(scans[scans.length - 1].created_at)) : null;
  const daysSinceLastScanNode = daysSinceLastScan === null
    ? null
    : daysSinceLastScan === 0
      ? <>Scanned <strong>today</strong></>
      : <><strong>{daysSinceLastScan.toLocaleString()} day{daysSinceLastScan === 1 ? "" : "s"}</strong> since last scan</>;

  const currentBlockersCount = chartData.data[chartData.data.length - 1].blockers;

  // "1,240 blockers · 87 unique issues" — the same DOM node repeated across
  // pages counts once here, so this is the real remediation workload.
  const uniqueBlockersCount = data?.uniqueBlockersCount ?? 0;
  const uniqueBlockersText = uniqueBlockersCount > 0
    ? <><strong>{uniqueBlockersCount.toLocaleString()}</strong> unique issue{uniqueBlockersCount === 1 ? "" : "s"} across all pages</>
    : null;

  // getAuditSummaryFast looks up each scan's own page count (via
  // jsonb_array_length on that scan's frozen `pages` array) rather than using
  // the audit's current URL list, so this stays correct even if URLs were
  // added or removed between the two scans being compared.
  const currentBlockersPerUrl = (data?.latestScan && data.latestScan.pagesCount > 0)
    ? data.latestScan.blockerCount / data.latestScan.pagesCount
    : null;
  const previousBlockersPerUrl = (data?.previousScan && data.previousScan.pagesCount > 0)
    ? data.previousScan.blockerCount / data.previousScan.pagesCount
    : null;
  const blockersPerUrlDelta = (currentBlockersPerUrl !== null && previousBlockersPerUrl !== null)
    ? Math.round((currentBlockersPerUrl - previousBlockersPerUrl) * 10) / 10
    : null;

  const blockersPerUrlDeltaText = blockersPerUrlDelta === null
    ? null
    : blockersPerUrlDelta > 0
      ? `${blockersPerUrlDelta.toFixed(1)} more blockers per URL since last scan`
      : blockersPerUrlDelta < 0
        ? `${Math.abs(blockersPerUrlDelta).toFixed(1)} fewer blockers per URL since last scan`
        : "No change since last scan";

  const pdfBlockersCount = data?.pdfBlockersCount ?? 0;
  const htmlBlockersCount = data?.htmlBlockersCount ?? 0;
  const totalTypedBlockersCount = pdfBlockersCount + htmlBlockersCount;
  const blockerTypeBreakdownText = totalTypedBlockersCount > 0
    ? `${pdfBlockersCount.toLocaleString()} PDF Blocker${pdfBlockersCount === 1 ? "" : "s"} (${((pdfBlockersCount / totalTypedBlockersCount) * 100).toFixed(1)}%) and ${htmlBlockersCount.toLocaleString()} HTML Blocker${htmlBlockersCount === 1 ? "" : "s"} (${((htmlBlockersCount / totalTypedBlockersCount) * 100).toFixed(1)}%)`
    : null;

  return (
    <div className={style["BlockersTableSummary"]}>
      {!error && !isLoading && data ? (
        <>
          <div className="cards-50">

            <Card className="short">
              {isFetching && !isLoading && <CardSpinner />}
              <div className={style["blockers-count"]}>
                <h3><span className="font-extra-large">{currentBlockersCount.toLocaleString()}</span> Blockers Found</h3>
                {(blockersDeltaText || daysSinceLastScanNode || uniqueBlockersText) && (
                  <div className={style["blockers-meta-group"]}>
                    {uniqueBlockersText && (
                      <p className={style["blockers-meta"]}>{uniqueBlockersText}</p>
                    )}
                    {blockersDeltaText && (
                      <p
                        className={style["blockers-delta"]}
                        style={{ color: blockersDelta! > 0 ? increaseColor : blockersDelta! < 0 ? decreaseColor : undefined }}
                      >
                        {blockersDeltaText}
                      </p>
                    )}
                    {daysSinceLastScanNode && (
                      <p className={style["blockers-meta"]}>{daysSinceLastScanNode}</p>
                    )}
                  </div>
                )}
              </div>
            </Card>
            <Card className="short" variant="light">
              {isFetching && !isLoading && <CardSpinner />}
              <div className={style["graph-card"]}>
                <div className={style["graph-card-text"]}>
                  <h3><span style={{ color: accentColor }}>{data.urlsWithBlockersCount}</span> of {pages.length} URLs (<span style={{ color: accentColor }}>{((data.urlsWithBlockersCount / pages.length) * 100).toFixed(1)}%</span>) in this audit have blockers.</h3>
                  {blockerTypeBreakdownText && (
                    <p className={style["blocker-type-breakdown"]}>{blockerTypeBreakdownText}</p>
                  )}
                </div>
                <ResponsiveContainer className={style["donut-chart"]}>
                  <PieChart {...{ "aria-hidden": "true" }}>
                    <Pie
                        data={[
                          {
                            name: "URLs with Blockers",
                            value: data.urlsWithBlockersCount,
                            fill: accentColor
                          },
                          {
                            name: "URLs without Blockers",
                            value: pages.length - data.urlsWithBlockersCount,
                            fill: chartFillSecondary
                          },
                        ]}
                        cx={"50%"}
                        cy={"50%"}
                        innerRadius={"70%"}
                        outerRadius={"100%"}
                        paddingAngle={0}
                        stroke="0"
                        dataKey="value"
                      >
                      </Pie>
                    </PieChart>
                  </ResponsiveContainer>
              </div>
            </Card>
            <Card variant="light" className="short">
              {isFetching && !isLoading && <CardSpinner />}
              <div className={style["blockers-count"]}>
                <h3><span className="font-extra-large">{currentBlockersPerUrl !== null ? currentBlockersPerUrl.toFixed(1) : "0.0"}</span> Blockers per URL</h3>
                {blockersPerUrlDeltaText && (
                  <div className={style["blockers-meta-group"]}>
                    <p
                      className={style["blockers-delta"]}
                      style={{ color: blockersPerUrlDelta! > 0 ? accentColor : blockersPerUrlDelta! < 0 ? decreaseColorOnLightVariant : undefined }}
                    >
                      {blockersPerUrlDeltaText}
                    </p>
                  </div>
                )}
              </div>
            </Card>
          </div>

          <div className={style["duplicates-filter"]}>
            <StyledLabeledInput>
              <label>Filter Duplicates</label>
              <select
                id="summaryDuplicatesToggleGroup"
                aria-label="Filter duplicates:"
                value={duplicatesMode}
                onChange={(e: ChangeEvent<HTMLSelectElement>) => handleDuplicatesModeChange(e.target.value)}
              >
                <option value="all">Show All Blockers</option>
                <option value="group">Group Duplicate Blockers</option>
                <option value="hide">Hide Duplicate Blockers</option>
              </select>
            </StyledLabeledInput>
          </div>

          <div className="cards-50">
            <Card variant="light">
              {isMostCommonUrlsFetching && !isMostCommonUrlsLoading && <CardSpinner />}
              <h3 id={urlsHeadingId}>URLs with Most Blockers</h3>
              {mostCommonUrlsError ? (
                <p>Couldn&apos;t load this list.</p>
              ) : !mostCommonUrlsData ? (
                <p>Loading…</p>
              ) : mostCommonUrlsData.totalCount === 0 ? (
                <p>No URLs with blockers found.</p>
              ) : (
                <>
                  <div role="table" aria-labelledby={urlsHeadingId}>
                    <DataRow asTableRow variant="highlight" the_value="Blockers" the_key="URL" />
                    {mostCommonUrlsData.items.map((item) => {
                      return <DataRow
                        asTableRow
                        key={item.key}
                        the_key={
                          <>
                            <Link
                              to={{ search: getUrlBlockersLinkSearch(item.key) }}
                              aria-label={`View ${item.count} blockers for ${item.key} in Detailed View`}
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
                          </>
                        }
                        the_value={
                          <Link
                            to={{ search: getUrlBlockersLinkSearch(item.key) }}
                            aria-label={`View ${item.count} blockers for ${item.key} in Detailed View`}
                          >
                            {item.count.toString()}
                          </Link>
                        }
                        variant="tight"
                      />
                    })}
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
            <Card variant="light">
              {isMostCommonBlockersFetching && !isMostCommonBlockersLoading && <CardSpinner />}
              <h3 id={blockersHeadingId}>Most Common Blockers</h3>
              {mostCommonBlockersError ? (
                <p>Couldn&apos;t load this list.</p>
              ) : !mostCommonBlockersData ? (
                <p>Loading…</p>
              ) : mostCommonBlockersData.totalCount === 0 ? (
                <p>No blockers found.</p>
              ) : (
                <>
                  <div role="table" aria-labelledby={blockersHeadingId}>
                    <DataRow asTableRow variant="highlight" the_value="Count" the_key="Blocker" />
                    {mostCommonBlockersData.items.map((item) => {
                      return <DataRow
                        asTableRow
                        key={item.key}
                        the_key={item.key}
                        the_value={
                          item.category ? (
                            <Link
                              to={{ search: getCategoryBlockersLinkSearch(item.category) }}
                              aria-label={`View ${item.count} blockers for ${item.key} in Detailed View`}
                            >
                              {item.count.toString()}
                            </Link>
                          ) : item.count.toString()
                        }
                        variant="tight"
                      />
                    })}
                  </div>
                  <PaginationControls
                    page={blockersPage}
                    totalPages={blockersTotalPages}
                    onPrevious={() => setBlockersPage((p) => Math.max(0, p - 1))}
                    onNext={() => setBlockersPage((p) => Math.min(blockersTotalPages - 1, p + 1))}
                    label="Most Common Blockers"
                  />
                </>
              )}
            </Card>
          </div>{/*
          <div className="cards-50">
            <Card variant="light">
              <div className={style["category-tag-card"]}>
                <div className={style["column"]}>
                  <h2>Most Common Blocker Categories</h2>
                  <DataRow variant="highlight" the_value="Count" the_key="Category" />
                    {data.mostCommonCategory.map((item, index) => {
                      return <DataRow
                        key={index}
                        the_key={<span className="tag">{item.key}</span>}
                        the_value={item.count.toString()}
                        variant="tight"
                      />
                    })}
                </div>
                <div className={style["column"]}>
                  <h2>Most Common Blocker Tags</h2>
                  <DataRow variant="highlight" the_value="Count" the_key="Tag" />
                  <ol>
                    {data.mostCommonTags.map((item, index) => {
                      return <DataRow
                        key={index}
                        the_key={<span className="tag">{item.key}</span>}
                        the_value={item.count.toString()}
                        variant="tight"
                      />
                    })}
                  </ol>
                </div>
              </div>
            </Card>
          </div>
 */}

        </>
      ) : (<><SkeletonAuditHeader /></>)}
    </div >
  );
};
