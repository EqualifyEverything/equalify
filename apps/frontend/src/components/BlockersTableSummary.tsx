import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import style from "./BlockersTableSummary.module.scss";
import * as API from "aws-amplify/api";
import { DataRow } from "./DataRow";
import { Card } from "./Card";
import { Page } from "#src/routes/Audit.tsx";
import { Pie, PieChart, ResponsiveContainer } from "recharts";

import themeVariables from "../global-styles/variables.module.scss";
import { SkeletonAuditHeader } from "./Skeleton";
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
  "key": string
}

interface SummaryResp {
  "mostCommonErrors": SummaryRespCountItem[],
  "urlsWithBlockersCount": number,
  "urlsWithMostErrors": SummaryRespCountItem[],
  "mostCommonCategory": SummaryRespCountItem[],
  "mostCommonTags": SummaryRespCountItem[]
}

export const BlockersTableSummary = ({ auditId, isShared, chartData, pages, scans }: BlockersTableSummaryProps) => {

  const [mostCommonUrlsLimit, setMostCommonUrlsLimit] = useState(5);
  const [mostCommonBlockersLimit, setMostCommonBlockersLimit] = useState(5);
  const [mostCommonCategoriesLimit, setMostCommonCategoriesLimit] = useState(3);
  const [mostCommonTagsLimit, setMostCommonTagsLimit] = useState(3);

  const { data, isLoading, error } = useQuery({
    queryKey: [
      "auditSummary"
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
        path: "/getAuditSummary",
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
      {data ? (
        <>
          <div className="cards-50">

            <Card className="short">
              <div className={style["blockers-count"]}>
                <h2><span className="font-extra-large">{chartData.data[chartData.data.length - 1].blockers.toLocaleString()}</span> Blockers Found</h2>
              </div>
            </Card>
            <Card className="short" variant="light">
              <div className={style["graph-card"]}>
                <h2><span style={{ color: themeVariables.red }}>{data.urlsWithBlockersCount}</span> of {pages.length} URLs (<span style={{ color: themeVariables.red }}>{((data.urlsWithBlockersCount / pages.length) * 100).toFixed(1)}%</span>) in this audit have blockers.</h2>
                <ResponsiveContainer className={style["donut-chart"]}>
                  <PieChart>
                    <Pie
                      data={[
                        {
                          name: "URLs with Blockers",
                          value: data.urlsWithBlockersCount,
                          fill: themeVariables.red
                        },
                        {
                          name: "URLs without Blockers",
                          value: pages.length - data.urlsWithBlockersCount,
                          fill: themeVariables.paper
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
                <h2><span className="font-extra-large">{daysSince(new Date(scans[scans.length - 1].created_at))}
                </span> Days Since Last Scanned </h2>
              </div>
            </Card>
          </div>

          <div className="cards-50">
            <Card variant="light">
              <h2>URLs with Most Blockers</h2>
              <DataRow variant="highlight" the_value="Blockers" the_key="URL" />
              {data.urlsWithMostErrors.map((item, index) => {
                return <DataRow
                  key={index}
                  the_key={<a href={item.key}>{item.key}</a>}
                  the_value={item.count.toString()}
                  variant="tight"
                />
              })}
            </Card>
            <Card variant="light">
              <h2>Most Common Blockers</h2>
              <DataRow variant="highlight" the_value="Count" the_key="Blocker" />
              {data.mostCommonErrors.map((item, index) => {
                return <DataRow
                  key={index}
                  the_key={item.key}
                  the_value={item.count.toString()}
                  variant="tight"
                />
              })}
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
