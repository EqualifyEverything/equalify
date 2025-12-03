import { useQuery, useQueryClient } from "@tanstack/react-query";
import { formatDate, useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { useLocation, useNavigate, useParams } from "react-router-dom";
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
  //Dot,
} from "recharts";
import { BlockersTable } from "../components/BlockersTable";
import { AuditPagesInput } from "#src/components/AuditPagesInput.tsx";
import {
  FaAngleDown,
  FaAngleUp,
} from "react-icons/fa";
import * as Tabs from "@radix-ui/react-tabs";
import * as Collapsible from "@radix-ui/react-collapsible";
import { createLog } from "#src/utils/createLog.ts";
import {
  AuditEmailSubscriptionInput,
  EmailSubscriptionList,
} from "#src/components/AuditEmailSubscriptionInput.tsx";
import * as Progress from "@radix-ui/react-progress";
import { Card } from "#src/components/Card.tsx";
import themeVariables from "../global-styles/variables.module.scss";
import { CustomizedDot } from "#src/components/ChartCustomizedDot.tsx";
import { ChartTooltipContent } from "#src/components/ChartTooltipContent.tsx";
import { AuditHeader } from "#src/components/AuditHeader.tsx";
interface Page {
  url: string;
  type: "html" | "pdf";
}

export const Audit = () => {
  const { auditId } = useParams();
  const queryClient = useQueryClient();
  const navigate = useNavigate();
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
  const [chartRange, setChartRange] = useState<number>(7);
  const isShared = location.pathname.startsWith("/shared/");
  const { setAriaAnnounceMessage } = useGlobalStore();
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
          query: `query($audit_id: uuid){scans(where:{audit_id:{_eq:$audit_id}},order_by: {created_at: asc}) {id created_at percentage}}`,
          variables: { audit_id: auditId },
        })
      )?.data?.scans,
    initialData: [],
    refetchInterval: 1000,
  });

  useEffect(() => {
    setPages(urls);
  }, [urls]);

  const { data: audit, refetch: refetchAudit } = useQuery({
    queryKey: ["audit", auditId],
    queryFn: async () =>
      (
        await apiClient.graphql({
          query: `query($audit_id: uuid!){audits_by_pk(id:$audit_id) {id name email_notifications}}`,
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
          options: { queryParams: { id: auditId!, days: chartRange } },
        }).response
      ).body.json();
      return results;
    },
    refetchInterval: 5000,
  });

  const handleUrlInput = async (_changedPages: Page[]) => {
    // just here to have a void function to hand to AuditPagesInput
    console.log("Url Input...");
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
    setAriaAnnounceMessage(
      `Added ${changedPages.length} URLs to audit ${audit.name}.`
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
    // aria
    setAriaAnnounceMessage(
      `Removed ${changedPages.length} URLs from audit ${audit.name}.`
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
    setAriaAnnounceMessage(
      `Changed ${changedPage.url} to type ${changedPage.type}.`
    );
    await createLog(
      `Changed ${changedPage.url} to type ${changedPage.type}.`,
      auditId
    );
  };

  useEffect(() => {
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

  return (
    <div>
      <AuditHeader 
        isShared={isShared}
        queryClient={queryClient}
        audit={audit}
        auditId={auditId}
      />
      <Card variant="red">
        {emailNotifications && (
          <>
            <span>
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
          </>
        )}
      </Card>
      <Card variant="green">
        <Collapsible.Root
          className="CollapsibleRoot"
          open={showUrlInput}
          onOpenChange={setShowUrlInput}
        >
          <div
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
            }}
          >
            <span>
              Audit: <b>{audit?.name}</b> <br /> Scanning {pages.length}{" "}
              <b>URL{pages.length > 1 ? "s" : ""}</b>
            </span>
            <Collapsible.Trigger>
              {showUrlInput ? <FaAngleDown /> : <FaAngleUp />}
              {isShared ? "View Audit URLs" : "View or Edit Audit URLs"}
            </Collapsible.Trigger>
          </div>
          <Collapsible.Content>
            <form>
              {pages.length > 0 && (
                <AuditPagesInput
                  initialPages={pages}
                  setParentPages={handleUrlInput}
                  addParentPages={addUrls}
                  removeParentPages={removeUrls}
                  updateParentPageType={updateUrlType}
                  returnMutation
                  isShared={isShared}
                />
              )}
            </form>
          </Collapsible.Content>
        </Collapsible.Root>
      </Card>
      <Card>
        {scans && scans?.length > 0 && (
          <div>
            <div>
              Last Scan: {formatDate(scans[scans.length - 1].created_at)}{" "}
            </div>
            <div className="flex">
              <Progress.Root
                value={scans[scans.length - 1].percentage}
                className="w-full bg-gray-200 h-4 rounded-full overflow-hidden"
              >
                <Progress.Indicator
                  className="bg-gray-800 w-full h-6"
                  style={{
                    transform: `translateX(-${100 - scans[scans.length - 1].percentage}%)`,
                  }}
                />
              </Progress.Root>
              <div>{scans[scans.length - 1].percentage}%</div>
            </div>
            <Collapsible.Root
              open={showAllScans}
              onOpenChange={setShowAllScans}
            >
              <Collapsible.CollapsibleTrigger>
                Show Scan History ({scans.length} Scans)
              </Collapsible.CollapsibleTrigger>
              <Collapsible.CollapsibleContent>
                <p>
                  Use this table to review every scan of this audit by date.
                </p>
                <div className="table-container">
                  <table>
                    <thead>
                      <tr>
                        <th>Scan</th>
                        <th>Date</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {scans?.map((scan, index) => (
                        <tr key={index}>
                          <td>#{index + 1}</td>
                          <td>{formatDate(scan.created_at)}</td>
                          <td>{scan.percentage ?? 0}%</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Collapsible.CollapsibleContent>
            </Collapsible.Root>
          </div>
        )}
      </Card>

      <Card variant="dark">
        {chartData?.data && chartData.data.length > 0 && (
          <div>
            <div className="blockers-chart-heading-wrapper">
              <h2 id="blockers-chart-heading">
                Blockers Over Time (Last {chartData.period_days} Days)
              </h2>
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
                <ResponsiveContainer width="100%" height={150}>
                  <LineChart
                    data={chartData.data}
                    //margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                    accessibilityLayer={true}
                    margin={{
                      top: 5,
                      right: 30,
                      left: 20,
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
                        console.log(index);
                        //if(index%5 === 0) return "";
                        const date = new Date(value);
                        return date.toLocaleDateString("en-US", {
                          month: "numeric",
                          day: "numeric",
                        });
                      }}
                      tickMargin={8}
                    />
                    {/* <YAxis
                      label={{
                        value: "Blockers",
                        angle: -90,
                        position: "insideLeft",
                      }}
                    /> */}
                    <Tooltip content={<ChartTooltipContent />} />
                    {/* <Legend /> */}
                    <Line
                      type="monotone"
                      dataKey="blockers"
                      stroke={themeVariables.paper}
                      strokeWidth={4}
                      dot={CustomizedDot}
                      name="Blockers"
                      isAnimationActive={false}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </Tabs.Content>
              <Tabs.Content value="table">
                <div>
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

      {auditId && <BlockersTable auditId={auditId} isShared={isShared} />}
    </div>
  );
};
