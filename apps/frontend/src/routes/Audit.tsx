import { useQuery, useQueryClient } from "@tanstack/react-query";
import { formatDate } from "../utils";
import * as API from "aws-amplify/api";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
const apiClient = API.generateClient();
import { useEffect, useState, ChangeEvent } from "react";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts";
import { BlockersTable } from "../components/BlockersTable";
import { AuditPagesInput } from "#src/components/AuditPagesInput.tsx";
import { FaAngleDown, FaAngleUp, FaClipboard } from "react-icons/fa";
import * as Tabs from "@radix-ui/react-tabs";
import * as Collapsible from "@radix-ui/react-collapsible";

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
  const [showUrlInput, setShowUrlInput] = useState<boolean>(false);
  const [chartRange, setChartRange] = useState<number>(7);
  const isShared = location.pathname.startsWith('/shared/');

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

  const { data: audit } = useQuery({
    queryKey: ["audit", auditId],
    queryFn: async () => (
      await apiClient.graphql({
        query: `query($audit_id: uuid!){audits_by_pk(id:$audit_id) {id name}}`,
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

  const renameAudit = async () => {
    const newName = prompt(
      `What would you like to rename this audit to?`,
      audit?.name
    );
    if (newName) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/updateAudit",
          options: { body: { id: auditId!, name: newName } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audit", auditId] });
      return;
    }
  };

  const rescanAudit = async () => {
    if (confirm(`Are you sure you want to re-scan this audit?`)) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/rescanAudit",
          options: { body: { id: auditId! } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audits"] });
      return;
    }
  };

  const deleteAudit = async () => {
    if (confirm(`Are you sure you want to delete this audit?`)) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/deleteAudit",
          options: { body: { id: auditId! } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audits"] });
      navigate("/audits");
      return;
    }
  };

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

      await apiClient.graphql({
        query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
        variables: {
          audit_id: auditId,
          message: `User added ${changedPage.url}`,
          data: { url: changedPage.url, type: changedPage.type },
        },
      });
    }
    await queryClient.refetchQueries({ queryKey: ["urls", auditId] });
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

      await apiClient.graphql({
        query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
        variables: {
          audit_id: auditId,
          message: `User removed ${row.url}`,
          data: { url: row.url, type: row.type },
        },
      });
    }
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
  };

  const copyCurrentLocationToClipboard = async () => {
    try {
      await navigator.clipboard.writeText(
        window.location.origin + location.pathname.replace('/audits/', '/shared/')
      );
      console.log(
        `URL ${window.location.origin + location.pathname} copied to clipboard!`
      );
    } catch (err) {
      console.error("Failed to copy URLs: ", err);
    }
  };

  return (
    <div className="max-w-screen-md">
      <div className="flex flex-col gap-2">
        {!isShared && <Link to={"/audits"}>← Go Back</Link>}
        <div className="flex flex-row items-center gap-2 justify-between">
          <h1 className="initial-focus-element">Audit: {audit?.name}</h1>
          <div className="flex flex-row items-center gap-2">
            {!isShared && <button onClick={renameAudit}>Rename</button>}
            {!isShared && <button onClick={rescanAudit}>Re-scan</button>}
            {!isShared && <button onClick={deleteAudit}>Delete</button>}
          </div>
        </div>
      </div>
      <hr />
      <button
        className="flex justify-center"
        onClick={copyCurrentLocationToClipboard}
      >
        <FaClipboard />
        <span>Copy link</span>
      </button>
      <hr />
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
            {isShared ? 'View Audit URLs' : 'View or Edit Audit URLs'}
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

      <hr />
      <div>
        {scans?.map((scan, index) => (
          <div key={index}>
            Scan #{index + 1}: {formatDate(scan.created_at)} (
            {scan.percentage ?? 0}%)
          </div>
        ))}
      </div>

      {chartData?.data && chartData.data.length > 0 && (
        <div className="mt-8 mb-8">
          <h2 id="blockers-chart-heading">
            Blockers Over Time (Last {chartData.period_days} Days)
          </h2>
          <label htmlFor="chart-range-select">Date Range:</label>
          <select
            id="chart-range-select"
            name="ChartRangeSelect"
            value={chartRange}
            onChange={(event: ChangeEvent<HTMLSelectElement>) => {
              setChartRange(parseInt(event.target.value))
            }}
            aria-label="Select Date Range"
          >
            <option value={7}>Week</option>
            <option value={30}>Month</option>
            <option value={90}>Quarter</option>
            <option value={365}>Year</option>
          </select>
          <Tabs.Root defaultValue="chart" orientation="vertical">
            <Tabs.List aria-label="Select a Chart View">
              <Tabs.Trigger value="chart">Chart View</Tabs.Trigger>
              <Tabs.Trigger value="table">Table View</Tabs.Trigger>
            </Tabs.List>
            <Tabs.Content value="chart">
              <div className="bg-white p-4 rounded-lg shadow">
                <ResponsiveContainer width="100%" height={300}>
                  <LineChart
                    data={chartData.data}
                    margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                    accessibilityLayer={true}
                    title="Blockers over time trend chart"
                    desc="Line chart showing blocker counts over time. See the data table below for detailed values."
                  >
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                      dataKey="date"
                      label={{
                        value: "Date",
                        position: "insideBottom",
                        offset: -5,
                      }}
                      tickFormatter={(value) => {
                        const date = new Date(value);
                        return date.toLocaleDateString("en-US", {
                          month: "short",
                          day: "numeric",
                        });
                      }}
                    />
                    <YAxis
                      label={{
                        value: "Blockers",
                        angle: -90,
                        position: "insideLeft",
                      }}
                    />
                    <Tooltip
                      contentStyle={{
                        backgroundColor: "white",
                        border: "1px solid #ccc",
                      }}
                      labelFormatter={(value) => {
                        const date = new Date(value);
                        return date.toLocaleDateString("en-US", {
                          weekday: "short",
                          year: "numeric",
                          month: "short",
                          day: "numeric",
                        });
                      }}
                      formatter={(value: number, name: string) => [
                        value,
                        name === "blockers" ? "Blockers" : name,
                      ]}
                    />
                    <Legend />
                    <Line
                      type="monotone"
                      dataKey="blockers"
                      stroke="#8884d8"
                      strokeWidth={2}
                      activeDot={{ r: 8 }}
                      name="Blockers"
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </Tabs.Content>
            <Tabs.Content value="table">
              <div className="mt-6">
                <h3>Blockers Data Table</h3>
                <p className="text-sm text-gray-600 mb-2">
                  Detailed data for the chart above. Use this table to access
                  exact blocker counts by date.
                </p>
                <table
                  className="w-full border-collapse border border-gray-300"
                  aria-labelledby="blockers-chart-heading"
                >
                  <thead>
                    <tr className="bg-gray-100">
                      <th
                        scope="col"
                        className="border border-gray-300 px-4 py-2 text-left"
                      >
                        Date
                      </th>
                      <th
                        scope="col"
                        className="border border-gray-300 px-4 py-2 text-left"
                      >
                        Blockers
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {chartData.data.map((row: any, index: number) => (
                      <tr
                        key={row.date}
                        className={index % 2 === 0 ? "bg-white" : "bg-gray-50"}
                      >
                        <td className="border border-gray-300 px-4 py-2">
                          {new Date(row.date).toLocaleDateString("en-US", {
                            weekday: "short",
                            year: "numeric",
                            month: "short",
                            day: "numeric",
                          })}
                        </td>
                        <td className="border border-gray-300 px-4 py-2">
                          {row.blockers}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Tabs.Content>
          </Tabs.Root>
        </div>
      )}

      {auditId && <BlockersTable auditId={auditId} isShared={isShared} />}
    </div>
  );
};
