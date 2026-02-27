import { useQuery, useQueryClient } from "@tanstack/react-query";
import { formatDate, useGlobalStore, unformatId } from "../utils";
import * as API from "aws-amplify/api";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
const apiClient = API.generateClient();
import { useEffect, useState, ChangeEvent } from "react";
import {
  LineChart,
  Line,
  XAxis,
  //YAxis,
  //CartesianGrid,
  Tooltip,
  //Legend,
  ResponsiveContainer,
  YAxis,
  //Dot,
} from "recharts";
import { BlockersTable } from "../components/BlockersTable";
import { AuditPagesInput } from "#src/components/AuditPagesInput.tsx";

import { TbHistory, TbMail, TbAlertTriangle, TbReload } from "react-icons/tb";
import { FaAngleDown, FaAngleUp, FaTable } from "react-icons/fa";
import { GrPowerCycle } from "react-icons/gr";

import * as Tabs from "@radix-ui/react-tabs";
import * as Collapsible from "@radix-ui/react-collapsible";
import * as Progress from "@radix-ui/react-progress";

import { createLog } from "#src/utils/createLog.ts";
import {
  AuditEmailSubscriptionInput,
  EmailSubscriptionList,
} from "#src/components/AuditEmailSubscriptionInput.tsx";

import { CustomizedDot } from "#src/components/ChartCustomizedDot.tsx";
import { ChartTooltipContent } from "#src/components/ChartTooltipContent.tsx";

import { AuditHeader } from "#src/components/AuditHeader.tsx";

import { Card } from "#src/components/Card.tsx";
import { Drawer } from "vaul-base";
import themeVariables from "../global-styles/variables.module.scss";
//import cardStyles from "../components/Card.module.scss";
import { PiFileMagnifyingGlassBold } from "react-icons/pi";
import { StyledButton } from "#src/components/StyledButton.tsx";
import style from "./Audit.module.scss";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { BlockersTableSummary } from "#src/components/BlockersTableSummary.tsx";
import { FaTableList } from "react-icons/fa6";
import { stringifyMessage } from "graphql-ws";

export interface Page {
  url: string;
  type: "html" | "pdf";
}

interface ScanError {
  type: string;
  message: string;
  urlId?: string;
  url?: string;
  timestamp: string;
  details?: object;
}

const formatErrorType = (type: string): string => {
  const errorLabels: Record<string, string> = {
    page_timeout: "Page Timeout",
    network_error: "Network Error",
    no_results: "No Results",
    scan_failed: "Scan Failed",
    blocker_processing_error: "Processing Error",
    scan_timeout: "Scan Timeout",
    no_urls: "No URLs Configured",
  };
  return errorLabels[type] || type;
};

export const Audit = () => {
  const { auditId: rawAuditId } = useParams();
  const auditId = rawAuditId ? unformatId(rawAuditId) : undefined;
  const queryClient = useQueryClient();
  //const navigate = useNavigate();
  const location = useLocation();
  const [pages, setPages] = useState<Page[]>([]);
  const [emailNotifications, setEmailNotifications] = useState<string | null>(
    null
  );
  const [emailNotificationsCount, setEmailNotificationsCount] =
    useState<number>(
      emailNotifications ? JSON.parse(emailNotifications).emails.length : 0
    );
  const [showUrlInput, setShowUrlInput] = useState<boolean>(false);
  const [showAllScans, setShowAllScans] = useState<boolean>(false);
  const [chartRange, setChartRange] = useState<number>(90);
  const [selectedScanErrors, setSelectedScanErrors] = useState<ScanError[]>([]);
  const isShared = location.pathname.startsWith("/shared/");
  const { setAnnounceMessage } = useGlobalStore();
  const { blockersTableView, setBlockersTableView } = useGlobalStore();

  useEffect(() => {
    if (emailNotifications)
      setEmailNotificationsCount(JSON.parse(emailNotifications).emails.length);
  }, [emailNotifications]);

  const { data: urls, isSuccess } = useQuery({
    queryKey: ["urls", auditId],
    queryFn: async () =>
      (
        await apiClient.graphql({
          query: `query($audit_id: uuid){urls(where:{audit_id:{_eq:$audit_id}},order_by: {created_at: desc}) {id url type}}`,
          variables: { audit_id: auditId },
        })
      )?.data?.urls,
    initialData: [],
  });

  const { data: scans } = useQuery({
    queryKey: ["scans", auditId],
    queryFn: async () =>
      (
        await apiClient.graphql({
          query: `query($audit_id: uuid){scans(where:{audit_id:{_eq:$audit_id}},order_by: {created_at: asc}) {id created_at percentage status errors}}`,
          variables: { audit_id: auditId },
        })
      )?.data?.scans,
    initialData: [],
    refetchInterval: 1000,
  });

  useEffect(() => {
    setPages(urls);
  }, [urls]);

  console.log(auditId);
  const { data: audit, refetch: refetchAudit } = useQuery({
    queryKey: ["audit", auditId],
    queryFn: async () =>
      (
        await apiClient.graphql({
          query: `query($audit_id: uuid!){audits_by_pk(id:$audit_id) {id name email_notifications interval remote_csv_url}}`,
          variables: { audit_id: auditId },
        })
      )?.data?.audits_by_pk,
  });

  const { data: chartData } = useQuery({
    queryKey: ["auditChart", auditId],
    queryFn: async () => {
      const results = await (
        await API.get({
          apiName: isShared ? "public" : "auth",
          path: "/getAuditChart",
          options: {
            queryParams: { id: auditId!, days: chartRange.toString() },
          },
        }).response
      ).body.json();
      return results;
    },
    //refetchInterval: 5000,
  });

  const handleUrlInput = async (_changedPages: Page[]) => {
    // just here to have a void function to hand to AuditPagesInput
    //console.log("Url Input...");
  };

  const addUrls = async (changedPages: Page[]) => {
    //TODO this should really be a mutation that accepts an array, not a loop
    for (const changedPage of changedPages) {
      await apiClient.graphql({
        query: `mutation ($audit_id: uuid, $url: String, $type: String) {
                insert_urls_one(object: {audit_id: $audit_id, url: $url, type: $type}) {id}
            }`,
        variables: {
          audit_id: auditId,
          url: changedPage.url,
          type: changedPage.type,
        },
      });

      await createLog(`URL added ${changedPage.url}`, auditId, {
        url: changedPage.url,
        type: changedPage.type,
      });
    }
    await queryClient.refetchQueries({ queryKey: ["urls", auditId] });
    // aria
    setAnnounceMessage(
      `Added ${changedPages.length} URLs to audit ${audit.name}.`,
      "success"
    );

    console.log("DB update complete.");
  };

  const removeUrls = async (changedPages: Page[]) => {
    console.log(`removing ${changedPages.length} URLs from db...`);
    for (const row of changedPages) {
      console.log(`removing ${row.url}`);
      await apiClient.graphql({
        query: `mutation($audit_id:uuid,$url:String) {delete_urls(where: {audit_id: {_eq: $audit_id}, url: {_eq: $url}}) {affected_rows}}`,
        variables: {
          audit_id: auditId,
          url: row.url,
        },
      });

      await createLog(`URL removed ${row.url}`, auditId, {
        url: row.url,
        type: row.type,
      });
    }

    await queryClient.refetchQueries({ queryKey: ["urls", auditId] });

    // aria
    setAnnounceMessage(
      `Removed ${changedPages.length} URLs from audit ${audit.name}.`,
      "success"
    );

    console.log("DB update complete.");
  };

  const updateUrlType = async (changedPage: Page) => {
    //console.log(changedPage);
    const updatedPage = await apiClient.graphql({
      query: `
        mutation ($audit_id: uuid, $url: String, $type: String) {
            update_urls(
                where: {
                    audit_id: {_eq: $audit_id},
                    _and: {url: {_eq: $url}}
                }, _set: {type: $type}
            ) 
                {
                    returning {
                        audit_id
                        type
                        updated_at
                        url
                        user_id
                }
            }
        }`,
      variables: {
        audit_id: auditId,
        url: changedPage.url,
        type: changedPage.type,
      },
    });
    console.log("DB Updated with new URL Type.", updatedPage);

    // aria & logging
    setAnnounceMessage(
      `Changed ${changedPage.url} to type ${changedPage.type}.`,
      "success"
    );
    await createLog(
      `Changed ${changedPage.url} to type ${changedPage.type}.`,
      auditId
    );
  };

  useEffect(() => {
    console.log(audit);
    if (audit?.email_notifications) {
      console.log("setting email notifications");
      setEmailNotifications(audit.email_notifications);
    }
  }, [audit]);

  const updateEmailNotifications = async (newValue: EmailSubscriptionList) => {
    //throw new Error("Function not implemented.");
    if (emailNotifications !== JSON.stringify(newValue)) {
      const newEmails = JSON.stringify(newValue);
      console.log("Updating email notifications:", newEmails);
      console.log("Email count", JSON.parse(newEmails).emails.length);
      setEmailNotificationsCount(JSON.parse(newEmails).emails.length);

      const updatedEmailNotifications = await apiClient.graphql({
        query: `mutation ($audit_id:uuid, $emails: String) {
                update_audits(where: {id: {_eq: $audit_id}}, _set: {email_notifications: $emails}) {
                  returning {
                    email_notifications
                  }
                }
              }`,
        variables: {
          audit_id: auditId,
          emails: newEmails,
        },
      });
      refetchAudit();
    }
  };

  const updateAuditInterval = async (newValue: string) => {
    console.log("Updating audit interval:", newValue);
    const updatedInterval = await apiClient.graphql({
      query: `mutation ($audit_id:uuid, $interval: String) {
                update_audits(where: {id: {_eq: $audit_id}}, _set: {interval: $interval}) {
                  returning {
                    interval
                  }
                }
              }`,
      variables: {
        audit_id: auditId,
        interval: newValue,
      },
    });
    refetchAudit();
  };

  const refreshUrlsFromCsv = async () => {
    console.log("Refreshing URL list from remote...")
    const resp = (await API.get({
      apiName: isShared ? "public" : "auth",
      path: "/syncFromRemoteCsv",
      options: {
        queryParams: { id: auditId! },
      },
    }).response);
    await queryClient.refetchQueries({ queryKey: ["urls", auditId] });
    const out = await resp.body.json() as any;

    setAnnounceMessage(
      out.message,
      "success"
    );
  }

  const updateAuditRemoteCsv = async (newValue: string) => {
    console.log("Updating remote CSV value:", newValue);
    const updatedRemoteCsvUrl = await apiClient.graphql({
      query: `mutation ($audit_id:uuid, $remote_csv_url: String) {
                update_audits(where: {id: {_eq: $audit_id}}, _set: {remote_csv_url: $remote_csv_url}) {
                  returning {
                    remote_csv_url
                  }
                }
              }`,
      variables: {
        audit_id: auditId,
        remote_csv_url: newValue,
      },
    });
    setAnnounceMessage(
      `Changed remote CSV URL to ${newValue}.`,
      "success"
    );
    refetchAudit();
  };

  /* TODO: implement function to set table filter by URL or by value 
    const setFilterAndOpenDetails = (type:string, value:string) => {
      switch(type){
        case "url":
          set
      }
      setBlockersTableView("detailed")
    }
   */

  return (
    <div className={style.Audit}>
      {/* {!isShared && <Link to={"/audits"} className="back-link">← Go Back</Link>}
       */}
      <AuditHeader
        isShared={isShared}
        queryClient={queryClient}
        audit={audit}
        auditId={auditId}
        scans={scans}
      />
      {/* Check scan state: not scanned, active scan, or completed */}
      {(() => {
        const hasNoScans = !scans || scans.length === 0;
        const hasActiveScan = scans && scans.length > 0 &&
          scans[scans.length - 1].status !== "complete" &&
          scans[scans.length - 1].status !== "failed";
        const currentScan = scans?.[scans.length - 1];

        // Not scanned yet state
        if (hasNoScans) {
          return (
            <Card variant="dark">
              <div style={{ padding: "40px 20px", textAlign: "center" }}>
                <h2 style={{ marginBottom: "16px", display: "flex", alignItems: "center", justifyContent: "center", gap: "8px" }}>
                  <TbHistory className="icon-small" />
                  Not Yet Scanned
                </h2>
                <p style={{ color: themeVariables.white, marginBottom: "16px", maxWidth: "500px", margin: "0 auto 16px" }}>
                  This audit hasn't been scanned yet. Add URLs below and run your first scan to discover accessibility blockers.
                </p>
              </div>
            </Card>
          );
        }

        // Active scan in progress
        if (hasActiveScan && currentScan) {
          return (
            <Card variant="dark">
              <div style={{ padding: "20px 0" }}>
                <h2 id="scan-progress-heading" style={{ marginBottom: "16px", display: "flex", alignItems: "center", gap: "8px" }}>
                  <GrPowerCycle className="icon-small" style={{ animation: "spin 1s linear infinite" }} />
                  Scanning...
                </h2>
                <p style={{ marginBottom: "100px", color: themeVariables.white }}>
                </p>
                <div style={{ display: "flex", alignItems: "center", gap: "16px" }}>
                  <Progress.Root
                    value={currentScan.percentage || 0}
                    className="w-full bg-gray-200 h-6 rounded-full overflow-hidden"
                    style={{ flex: 1, height: "24px", backgroundColor: "rgba(255,255,255,0.2)", borderRadius: "12px", overflow: "hidden" }}
                  >
                    <Progress.Indicator
                      style={{
                        width: "100%",
                        height: "100%",
                        backgroundColor: themeVariables.green,
                        transition: "transform 0.3s ease",
                        transform: `translateX(-${100 - (currentScan.percentage || 0)}%)`,
                      }}
                    />
                  </Progress.Root>
                  <span style={{ fontWeight: "bold", fontSize: "1.25em", minWidth: "60px", textAlign: "right" }}>
                    {currentScan.percentage || 0}%
                  </span>
                </div>
              </div>
            </Card>
          );
        }

        // Check if the last scan timed out
        const lastScanTimedOut = currentScan?.errors?.some(
          (e: ScanError) => e.type === 'scan_timeout'
        );

        // Completed scan - show timeout notice if applicable, then chart
        return (
          <>
            {lastScanTimedOut && (
              <Card variant="dark">
                <div style={{ padding: "20px 0", display: "flex", alignItems: "center", gap: "12px" }}>
                  <TbAlertTriangle style={{ fontSize: "1.5em", color: themeVariables.yellow || "#f0ad4e", flexShrink: 0 }} />
                  <div>
                    <h3 style={{ margin: 0 }}>Scan Timed Out</h3>
                    <p style={{ margin: "4px 0 0", color: themeVariables.white, fontSize: "0.9em" }}>
                      The last scan did not complete within the expected time. Some results may be missing.
                      {currentScan.percentage != null && currentScan.percentage > 0 && currentScan.percentage < 100
                        ? ` Only ${currentScan.percentage}% of pages were scanned.`
                        : ''}
                      {' '}Try rescanning — if the issue persists, some pages may be unreachable.
                    </p>
                  </div>
                </div>
              </Card>
            )}
            <Card variant="dark" className="blockers-chart">
              {chartData?.data && chartData.data.length > 0 && (
                <div>
                  <div className="blockers-chart-heading-wrapper">
                    <div>
                      <h2 id="blockers-chart-heading">
                        <TbHistory className="icon-small" />
                        {chartData.data[
                          chartData.data.length - 1
                        ].blockers.toLocaleString()}{" "}
                        Blockers
                      </h2>
                      <span className="font-small">
                        Last {chartData.period_days} Days:
                      </span>
                    </div>
                    <div className="chart-ranger-select">
                      <label htmlFor="chart-range-select">Date Range</label>
                      <select
                        id="chart-range-select"
                        name="ChartRangeSelect"
                        value={chartRange}
                        onChange={(event: ChangeEvent<HTMLSelectElement>) => {
                          setChartRange(parseInt(event.target.value));
                        }}
                        aria-label="Select Date Range"
                      >
                        <option value={7}>Week</option>
                        <option value={30}>Month</option>
                        <option value={90}>Quarter</option>
                        <option value={365}>Year</option>
                      </select>
                    </div>
                  </div>
                  <Tabs.Root
                    defaultValue="chart"
                    orientation="horizontal"
                    className="chart-tabs"
                  >
                    <Tabs.List aria-label="Select a Chart View">
                      <Tabs.Trigger value="chart" className="trigger">
                        Chart View
                      </Tabs.Trigger>
                      <Tabs.Trigger value="table" className="trigger">
                        Table View
                      </Tabs.Trigger>
                    </Tabs.List>
                    <Tabs.Content value="chart">
                      <ResponsiveContainer width="100%" height={170}>
                        <LineChart
                          data={chartData.data}
                          //margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                          accessibilityLayer={true}
                          margin={{
                            top: 5,
                            right: 10,
                            left: -20,
                            bottom: 5,
                          }}
                          title="Blockers over time trend chart"
                          desc="Line chart showing blocker counts over time. See the data table below for detailed values."
                        >
                          {/* <CartesianGrid strokeDasharray="6 6" />
                     */}
                          <XAxis
                            dataKey="date"
                            type={"category"}
                            /* label={{
                              value: "Date",
                              position: "insideBottom",
                              offset: -5,
                            }} */
                            tickFormatter={(value, index) => {
                              if (index % 2) return "";
                              //console.log(index);
                              //if(index%5 === 0) return "";
                              const date = new Date(value);
                              return date.toLocaleDateString("en-US", {
                                month: "numeric",
                                day: "numeric",
                              });
                            }}
                            tickMargin={8}
                          />
                          <YAxis
                            //axisLine={false}
                            orientation="left"
                            label={{/* 
                        value: "Blockers", */
                              angle: -90,
                              position: "insideLeft",
                            }}
                          />
                          <Tooltip content={<ChartTooltipContent />} />
                          {/* <Legend /> */}
                          <Line
                            type="monotone"
                            dataKey="blockers"
                            stroke={themeVariables.white}
                            strokeWidth={4}
                            dot={CustomizedDot}
                            name="Blockers"
                            isAnimationActive={false}
                          />
                        </LineChart>
                      </ResponsiveContainer>
                    </Tabs.Content>
                    <Tabs.Content value="table">
                      <div className={style["blockers-data-table"]}>
                        <h3>Blockers Data Table</h3>
                        <p>Use this table to review exact blocker counts by date.</p>
                        <div className={"table-container card-table"}>
                          <table aria-labelledby="blockers-chart-heading">
                            <thead>
                              <tr>
                                <th scope="col">Scan Date</th>
                                <th scope="col">Blockers</th>
                              </tr>
                            </thead>
                            <tbody>
                              {chartData.data.map((row: any, index: number) => {
                                if (row.timestamp) {
                                  //console.log(row);
                                  return (
                                    <tr key={row.date}>
                                      <td>
                                        {new Date(row.date).toLocaleDateString(
                                          "en-US",
                                          {
                                            weekday: "short",
                                            year: "numeric",
                                            month: "short",
                                            day: "numeric",
                                          }
                                        )}
                                      </td>
                                      <td>{row.blockers}</td>
                                    </tr>
                                  );
                                } else {
                                  return false;
                                }
                              })}
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </Tabs.Content>
                  </Tabs.Root>
                </div>
              )}
            </Card>
          </>
        );
      })()}

      <div className={"cards-62-38 " + style["scan-cards-area"]}>
        <Card variant="light" className={style["urls-card"]}>
          <Collapsible.Root
            className="CollapsibleRoot"
            open={showUrlInput}
            onOpenChange={setShowUrlInput}
          >
            <div className={style["scan-url-card-header"]}>
              <h2>
                <PiFileMagnifyingGlassBold className="icon-small" />
                {pages.length} URL{pages.length > 1 ? "s" : ""} Included
              </h2>


              {audit && (
                <StyledLabeledInput>
                  <label htmlFor="scanFrequency">Scan Frequency:</label>
                  <select
                    id="scanFrequency"
                    name="scanFrequency"
                    className={themeVariables["input-element"]}
                    value={audit.interval}
                    onChange={(event: ChangeEvent<HTMLSelectElement>) => {
                      updateAuditInterval(event.target.value);
                    }}
                  >
                    <option>Manually</option>
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                  </select>
                </StyledLabeledInput>
              )}
            </div>

            <div>

              {audit?.remote_csv_url ? (
                <Card variant="inset-light" className={style["remote-csv-area"]}>
                  <div className={style["remote-csv-area-inputs"]}>
                    <StyledLabeledInput className={style["remote-csv-area-inputs-labeled-input"]}>
                      <label htmlFor="remote-csv-input">Remote CSV URL</label>
                      <input
                        value={audit?.remote_csv_url}
                        onChange={(event: ChangeEvent<HTMLInputElement>) => {
                          updateAuditRemoteCsv(event.target.value);
                        }}
                      />
                    </StyledLabeledInput>
                    <StyledButton
                      icon={<TbReload />}
                      label="Refresh URL list from CSV"
                      onClick={refreshUrlsFromCsv}
                      showLabel={false}
                      variant="naked"
                    />
                  </div>
                  
                  <p className="font-small" style={{ marginBottom: 0}}>This audit will automatically update the list of URLs scanned from the CSV above.</p>
                </Card>
              ) : (null)}

            </div>
            <div>
              {scans && scans?.length > 0 && (
                <div>
                  <div className="font-small">
                    Last Scan: {formatDate(scans[scans.length - 1].created_at)}{" "}
                    {scans[scans.length - 1].status && (
                      <span
                        style={{
                          textTransform: "capitalize",
                          fontWeight: "bold",
                          color:
                            scans[scans.length - 1].status === "complete"
                              ? themeVariables.green
                              : scans[scans.length - 1].status === "failed"
                                ? themeVariables.red
                                : "inherit",
                        }}
                      >
                        ({scans[scans.length - 1].status})
                      </span>
                    )}
                  </div>
                  {/* Display scan errors if any */}
                  {scans[scans.length - 1].errors &&
                    scans[scans.length - 1].errors.length > 0 && (
                      <div>
                        <Drawer.Root
                          direction="right"
                          shouldScaleBackground
                          setBackgroundColorOnScale={false}
                          onOpenChange={(open) => {
                            if (open) {
                              setSelectedScanErrors(
                                scans[scans.length - 1].errors
                              );
                            }
                          }}
                        >
                          <Drawer.Trigger
                            aria-label={`View ${scans[scans.length - 1].errors.length} scan errors`}
                            className={style["view-errors-button"]}
                          >
                            <TbAlertTriangle />
                            <span>{
                              scans[scans.length - 1].errors.length +
                              " Error" +
                              (scans[scans.length - 1].errors.length > 1 ? "s" : "") +
                              " During Scan"
                            }</span>
                          </Drawer.Trigger>
                          <Drawer.Portal>
                            <Drawer.Overlay className="drawer-overlay" />
                            <Drawer.Content className="drawer-content">
                              <div className="drawer-content-inner">
                                <h4
                                  style={{
                                    display: "flex",
                                    alignItems: "center",
                                    gap: "8px",
                                    margin: "0 0 16px 0",
                                  }}
                                >
                                  <TbAlertTriangle
                                    style={{ color: themeVariables.red }}
                                    aria-hidden="true"
                                  />
                                  Scan Errors ({selectedScanErrors.length})
                                </h4>
                                <p style={{ marginBottom: "16px" }}>
                                  The following pages encountered errors during
                                  scanning:
                                </p>
                                <div
                                  style={{
                                    maxHeight: "60vh",
                                    overflowY: "auto",
                                  }}
                                >
                                  {selectedScanErrors.map(
                                    (err: ScanError, idx: number) => (
                                      <div
                                        key={idx}
                                        style={{
                                          marginBottom: "16px",
                                          padding: "12px",
                                          backgroundColor: `${themeVariables.red}10`,
                                          borderRadius: "4px",
                                          border: `1px solid ${themeVariables.red}30`,
                                        }}
                                      >
                                        <div style={{ marginBottom: "8px" }}>
                                          <span
                                            style={{
                                              display: "inline-block",
                                              padding: "2px 8px",
                                              backgroundColor: `${themeVariables.red}20`,
                                              borderRadius: "4px",
                                              fontSize: "0.875em",
                                              fontWeight: "bold",
                                            }}
                                          >
                                            {formatErrorType(err.type)}
                                          </span>
                                        </div>
                                        {err.url && (
                                          <div
                                            style={{
                                              marginBottom: "8px",
                                              wordBreak: "break-all",
                                            }}
                                          >
                                            <strong>URL:</strong>{" "}
                                            <a
                                              href={err.url}
                                              target="_blank"
                                              rel="noopener noreferrer"
                                            >
                                              {err.url}
                                            </a>
                                          </div>
                                        )}
                                        {err.message && (
                                          <Collapsible.Root>
                                            <Collapsible.Trigger
                                              style={{
                                                background: "none",
                                                border: "none",
                                                cursor: "pointer",
                                                textDecoration: "underline",
                                                padding: 0,
                                                color: "inherit",
                                                fontSize: "0.875em",
                                              }}
                                            >
                                              View error details
                                            </Collapsible.Trigger>
                                            <Collapsible.Content>
                                              <pre
                                                style={{
                                                  margin: "8px 0 0 0",
                                                  padding: "8px",
                                                  backgroundColor:
                                                    themeVariables.black,
                                                  color: themeVariables.paper,
                                                  borderRadius: "4px",
                                                  fontSize: "0.75em",
                                                  whiteSpace: "pre-wrap",
                                                  wordBreak: "break-word",
                                                }}
                                              >
                                                {err.message}
                                                {err.timestamp && (
                                                  <>
                                                    {"\n\n"}Time:{" "}
                                                    {new Date(
                                                      err.timestamp
                                                    ).toLocaleString()}
                                                  </>
                                                )}
                                              </pre>
                                            </Collapsible.Content>
                                          </Collapsible.Root>
                                        )}
                                      </div>
                                    )
                                  )}
                                </div>
                                <Drawer.Close className="drawer-content-close">
                                  Close
                                </Drawer.Close>
                              </div>
                            </Drawer.Content>
                          </Drawer.Portal>
                        </Drawer.Root>
                      </div>
                    )}
                </div>
              )}

              <Collapsible.Trigger asChild>
                <StyledButton
                  variant="naked"
                  label={
                    isShared ? "View Audit URLs" : "View or Edit Audit URLs"
                  }
                  icon={!showUrlInput ? <FaAngleDown /> : <FaAngleUp />}
                  onClick={() => { }}
                />
              </Collapsible.Trigger>
            </div>
            <Collapsible.Content>
              <form>
                <AuditPagesInput
                  initialPages={pages}
                  setParentPages={handleUrlInput}
                  addParentPages={addUrls}
                  removeParentPages={removeUrls}
                  updateParentPageType={updateUrlType}
                  returnMutation
                  isShared={isShared || audit?.remote_csv_url}
                />
              </form>
            </Collapsible.Content>
          </Collapsible.Root>
        </Card>
        <Card variant="light">
          {emailNotifications && (
            <div>
              <h2>
                <TbMail className="icon-small" /> Email Notifications
              </h2>
              <span className="font-small">
                {emailNotificationsCount > 0
                  ? `${emailNotificationsCount} Email Notifications`
                  : "No Email Notifications"}
              </span>
              <div>
                <AuditEmailSubscriptionInput
                  initialValue={JSON.parse(emailNotifications)}
                  onValueChange={updateEmailNotifications}
                />
              </div>
            </div>
          )}
        </Card>
      </div>
      {
        chartData?.data
        && chartData.data.length > 0
        && auditId
        && pages
        && scans
        && scans.length > 0
        &&
        <Tabs.Root
          orientation="horizontal"
          className="audit-tabs"
          value={blockersTableView}
          onValueChange={(value) => setBlockersTableView(value)}
          activationMode="manual"
        >
          <div className={style["blockers-table-header"]} style={{ flexDirection: blockersTableView === "summary" ? "row" : "row-reverse" }}>
            <h3>Audit Report <span className="font-normal">{blockersTableView === "summary" ? "Summary View" : "Detailed View"}</span></h3>

            <Tabs.List aria-label="Select a View" className={style["blockers-view-selector"]}>
              {blockersTableView !== "summary" &&
                <Tabs.Trigger value="summary" className={style["blockers-view-trigger"]} asChild>
                  <StyledButton variant="naked" label="Switch to Summary View" onClick={() => { }}>Switch to Summary View</StyledButton>
                </Tabs.Trigger>
              }
              {blockersTableView !== "detailed" &&
                <Tabs.Trigger value="detailed" className={style["blockers-view-trigger"]} asChild>
                  <StyledButton variant="naked" label="Switch to Detailed View" onClick={() => { }}>Switch to Detailed View</StyledButton>
                </Tabs.Trigger>
              }
            </Tabs.List>
          </div>
          <Tabs.Content value="summary">
            <BlockersTableSummary
              chartData={chartData}
              isShared={isShared}
              auditId={auditId}
              pages={pages}
              scans={scans}
            /* filterLinkHandler={setFilterAndOpenDetails} */
            />
          </Tabs.Content>
          <Tabs.Content value="detailed">
            {auditId && <BlockersTable auditId={auditId} isShared={isShared} />}

          </Tabs.Content>
        </Tabs.Root>
      }

    </div>
  );
};
