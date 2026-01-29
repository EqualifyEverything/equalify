import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import style from "./BlockersTableSummary.module.scss";
import * as API from "aws-amplify/api";
//const apiClient = API.generateClient();

interface BlockersTableSummaryProps {
  auditId: string;
  isShared: boolean;
  chartData: any;
}

export const BlockersTableSummary = ({ auditId, isShared, chartData }: BlockersTableSummaryProps) => {

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
                path: "/getAuditTable",
                options: { queryParams: params },
        }).response;
        const resp = (await response.body.json()) as any;
        console.log(resp);
        return resp;
    }});

  return (
    <div className={style.BlockersTableSummary}>
      Summary
      --<br/>
      {chartData.data[
        chartData.data.length - 1
      ].blockers.toLocaleString()} Blockers.
      <br/>
      X of Y pages have accessibility errors.
      The average page has x.x accessibility errors.
      --
      Pages with most errors:
      --
      The most common accessibility errors:
      | Table |
      --
      The most common tag/category of error (table w/ count?)
    </div>
  );
};
