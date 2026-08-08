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

interface SummaryResp {
  "mostCommonErrors": SummaryRespCountItem[],
  "urlsWithBlockersCount": number,
  "urlsWithMostErrors": SummaryRespCountItem[],
  "mostCommonCategory": SummaryRespCountItem[],
  "mostCommonTags": SummaryRespCountItem[]
}

export const BlockersTableSummary = ({ auditId, isShared, chartData, pages, scans }: BlockersTableSummaryProps) => {

  const { darkMode } = useGlobalStore();
  const accentColor = darkMode ? themeVariables.yellow : themeVariables.red;
  const chartFillSecondary = darkMode ? themeVariables.dark_border : themeVariables.paper;

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

  return (
    <div className={style["BlockersTableSummary"]}>
      {!error && !isLoading && data ? (
        <>
          <div className="cards-50">

            <Card className="short">
              <div className={style["blockers-count"]}>
                <h3><span className="font-extra-large">{chartData.data[chartData.data.length - 1].blockers.toLocaleString()}</span> Blockers Found</h3>
              </div>
            </Card>
            <Card className="short" variant="light">
              <div className={style["graph-card"]}>
                <h3><span style={{ color: accentColor }}>{data.urlsWithBlockersCount}</span> of {pages.length} URLs (<span style={{ color: accentColor }}>{((data.urlsWithBlockersCount / pages.length) * 100).toFixed(1)}%</span>) in this audit have blockers.</h3>
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
                <h3><span className="font-extra-large">{daysSince(new Date(scans[scans.length - 1].created_at))}
                </span> Days Since Last Scanned </h3>
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
