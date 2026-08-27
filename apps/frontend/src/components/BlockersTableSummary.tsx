import { useQuery } from "@tanstack/react-query";
import { useId, useState } from "react";
import style from "./BlockersTableSummary.module.scss";
import * as API from "aws-amplify/api";
import { DataRow } from "./DataRow";
import { Card } from "./Card";
import { Page } from "#src/routes/Audit.tsx";
import { Pie, PieChart, ResponsiveContainer } from "recharts";
import { GrPowerCycle } from "react-icons/gr";
import { Link, useSearchParams } from "react-router-dom";

import themeVariables from "../global-styles/variables.module.scss";
import { SkeletonAuditHeader } from "./Skeleton";
import { useGlobalStore } from "#src/utils";
//const apiClient = API.generateClient();

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
  "mostCommonErrors": SummaryRespCountItem[],
  "urlsWithBlockersCount": number,
  "urlsWithMostErrors": SummaryRespCountItem[],
  "mostCommonCategory": SummaryRespCountItem[],
  "mostCommonTags": SummaryRespCountItem[],
  "latestScan": SummaryRespScan | null,
  "previousScan": SummaryRespScan | null,
  "pdfBlockersCount": number,
  "htmlBlockersCount": number
}

export const BlockersTableSummary = ({ auditId, isShared, chartData, pages, scans }: BlockersTableSummaryProps) => {

  const { darkMode } = useGlobalStore();
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

  const [mostCommonUrlsLimit, setMostCommonUrlsLimit] = useState(5);
  const [mostCommonBlockersLimit, setMostCommonBlockersLimit] = useState(5);
  const [mostCommonCategoriesLimit, setMostCommonCategoriesLimit] = useState(3);
  const [mostCommonTagsLimit, setMostCommonTagsLimit] = useState(3);

  const { data, isLoading, isFetching, error } = useQuery({
    queryKey: [
      "auditSummary", auditId,
      mostCommonUrlsLimit, mostCommonBlockersLimit,
      mostCommonCategoriesLimit, mostCommonTagsLimit
    ],
    queryFn: async () => {
      const params: Record<string, string> = {
        id: auditId,
        mostCommonUrlsLimit: mostCommonUrlsLimit.toString(),
        mostCommonBlockersLimit: mostCommonBlockersLimit.toString(),
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
              <div className={style["blockers-count"]}>
                <h3><span className="font-extra-large">{currentBlockersCount.toLocaleString()}</span> Blockers Found</h3>
                {(blockersDeltaText || daysSinceLastScanNode) && (
                  <div className={style["blockers-meta-group"]}>
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

          <div className="cards-50">
            <Card variant="light">
              <h3 id={urlsHeadingId}>URLs with Most Blockers</h3>
              <div role="table" aria-labelledby={urlsHeadingId}>
                <DataRow asTableRow variant="highlight" the_value="Blockers" the_key="URL" />
                {data.urlsWithMostErrors.map((item, index) => {
                  return <DataRow
                    asTableRow
                    key={index}
                    the_key={<a href={item.key} target="_blank">{item.key}</a>}
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
            </Card>
            <Card variant="light">
              <h3 id={blockersHeadingId}>Most Common Blockers</h3>
              <div role="table" aria-labelledby={blockersHeadingId}>
                <DataRow asTableRow variant="highlight" the_value="Count" the_key="Blocker" />
                {data.mostCommonErrors.map((item, index) => {
                  return <DataRow
                    asTableRow
                    key={index}
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
      {isFetching && !isLoading && (
        <span role="img" aria-label="Refreshing">
          <GrPowerCycle className={style.spinning} />
        </span>
      )}
    </div >
  );
};
