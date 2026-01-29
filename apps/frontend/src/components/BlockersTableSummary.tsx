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
  pages: Page[]
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

export const BlockersTableSummary = ({ auditId, isShared, chartData, pages }: BlockersTableSummaryProps) => {

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
      console.log(resp);
      return resp;
    }
  });

  return (
    <div className={style["BlockersTableSummary"]}>
      <h2>Audit Summary</h2>
      {data ? (
        <>
          <div className="cards-50">
            <Card>
              <h2>{pages.length} of {data.urlsWithBlockersCount} URLs Have Blockers</h2>
              <ResponsiveContainer width="100%" height={120}>
                <PieChart>
                  <Pie
                    data={[
                      {
                        name: "URLs with Blockers",
                        value: data.urlsWithBlockersCount,
                        fill: themeVariables.white
                      },
                      {
                        name: "URLs without Blockers",
                        value: pages.length - data.urlsWithBlockersCount,
                        fill: themeVariables.black
                      },
                    ]}
                    cx={60}
                    cy={60}
                    innerRadius={30}
                    outerRadius={40}
                    paddingAngle={0}
                    stroke="0"
                    dataKey="value"
                  >
                  </Pie>
                </PieChart>
              </ResponsiveContainer>
            </Card>
            <Card>
              <h2>{chartData.data[chartData.data.length - 1].blockers.toLocaleString()}</h2> Blockers Found
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
          </div>

          <div className="cards-50">
            <Card variant="light">
              <h2>Most Common Blocker Category</h2>
              <DataRow variant="highlight" the_key="Count" the_value="Category" />
              {data.mostCommonCategory.map((item, index) => {
                return <DataRow
                  key={index}
                  the_value={item.key}
                  the_key={item.count.toString()}
                  variant="tight"
                />
              })}
            </Card>
            <Card variant="light">
              <h2>Most Common Blocker Tag</h2>
              <DataRow variant="highlight" the_key="Count" the_value="Tag" />
              {data.mostCommonTags.map((item, index) => {
                return <DataRow
                  key={index}
                  the_value={item.key}
                  the_key={item.count.toString()}
                  variant="tight"
                  />
              })}
            </Card>
          </div>

        </>
      ) : (<><SkeletonAuditHeader /></>)}
    </div >
  );
};
