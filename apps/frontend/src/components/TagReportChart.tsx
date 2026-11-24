import { ChangeEvent } from "react";
import * as Tabs from "@radix-ui/react-tabs";
import { Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { TbHistory } from "react-icons/tb";
import { GrPowerCycle } from "react-icons/gr";
import { Card } from "./Card";
import { SkeletonChart } from "./Skeleton";
import { CustomizedActiveDot, CustomizedDot } from "./ChartCustomizedDot";
import tooltipStyles from "./ChartTooltipContent.module.scss";
import themeVariables from "../global-styles/variables.module.scss";
import styles from "./TagReportChart.module.scss";

export interface TagReportChartPoint {
  date: string; // YYYY-MM-DD (UTC)
  blockers: number;
  auditsWithData: number;
  scansCompleted: number;
  timestamp: string | null; // last completed scan that day, if any
}

export interface TagReportChartData {
  period_days: number;
  data: TagReportChartPoint[];
}

interface TagReportChartProps {
  chartData: TagReportChartData | undefined;
  isLoading: boolean;
  isFetching: boolean;
  days: number;
  onDaysChange: (days: number) => void;
}

// Dates arrive as UTC YYYY-MM-DD keys; build a local date from the parts so
// it doesn't shift a day in timezones west of UTC
const parseDateKey = (value: string) => {
  const [year, month, day] = value.split("-").map(Number);
  return new Date(year, month - 1, day);
};

const formatAxisNumber = (value: number) => {
  if (value >= 1_000_000) return `${(value / 1_000_000).toFixed(value % 1_000_000 === 0 ? 0 : 1)}M`;
  if (value >= 1_000) return `${(value / 1_000).toFixed(value % 1_000 === 0 ? 0 : 1)}k`;
  return String(value);
};

const scansLabel = (count: number) => `${count.toLocaleString()} scan${count === 1 ? "" : "s"} completed`;

const TagReportTooltip = ({ active, payload, label }: { active?: boolean; payload?: any[]; label?: string }) => {
  if (!active || !payload?.length || !label) return null;
  const point = payload[0].payload as TagReportChartPoint;
  return (
    <div className={tooltipStyles.tooltip}>
      <div className="block">
        {parseDateKey(label).toLocaleDateString("en-US", { weekday: "short", year: "numeric", month: "short", day: "numeric" })}
      </div>
      <div className="block">
        Combined blockers: <b>{point.blockers.toLocaleString()}</b>
      </div>
      {point.scansCompleted > 0 && (
        <div className="block">
          <b>{scansLabel(point.scansCompleted)}</b>
        </div>
      )}
    </div>
  );
};

// Combined blockers over time for a Tag Report. Mirrors the Audit page's
// chart card (date range, chart/table tabs) so the two read the same way.
export const TagReportChart = ({ chartData, isLoading, isFetching, days, onDaysChange }: TagReportChartProps) => {
  const points = chartData?.data ?? [];
  const latest = points[points.length - 1];
  // The table lists only days something changed, rather than every day
  const scanDays = points.filter((point) => point.scansCompleted > 0).reverse();

  return (
    <Card variant="dark" className="blockers-chart">
      {isLoading && <SkeletonChart />}
      {!isLoading && latest && (
        <div>
          <div className="blockers-chart-heading-wrapper">
            <div>
              <h2 id="tag-report-chart-heading">
                <TbHistory className="icon-small" />
                {latest.blockers.toLocaleString()} Combined Blockers
              </h2>
              <span className="font-small">
                Last {chartData!.period_days} Days:
                {isFetching && (
                  <span
                    role="status"
                    aria-label="Refreshing chart data"
                    style={{ marginLeft: 8, display: "inline-flex", verticalAlign: "middle" }}
                  >
                    <GrPowerCycle className="icon-small" style={{ animation: "spin 1s linear infinite" }} />
                  </span>
                )}
              </span>
            </div>
            <div className="chart-ranger-select">
              <label htmlFor="tag-report-range-select">Date Range</label>
              <select
                id="tag-report-range-select"
                value={days}
                onChange={(event: ChangeEvent<HTMLSelectElement>) => onDaysChange(parseInt(event.target.value))}
              >
                <option value={7}>Week</option>
                <option value={30}>Month</option>
                <option value={90}>Quarter</option>
                <option value={365}>Year</option>
              </select>
            </div>
          </div>
          <Tabs.Root defaultValue="chart" orientation="horizontal" className="chart-tabs">
            <span id="tag-report-chart-tabs-hint" className="sr-only">
              Both views show the same combined blockers-over-time data.
              Table view presents it as text instead of a chart.
            </span>
            <Tabs.List aria-label="Combined blockers over time: chart or table view">
              <Tabs.Trigger value="chart" className="trigger" aria-describedby="tag-report-chart-tabs-hint">
                Chart View
              </Tabs.Trigger>
              <Tabs.Trigger value="table" className="trigger" aria-describedby="tag-report-chart-tabs-hint">
                Table View
              </Tabs.Trigger>
            </Tabs.List>
            <Tabs.Content value="chart">
              <ResponsiveContainer width="100%" height={170}>
                <LineChart
                  data={points}
                  accessibilityLayer={true}
                  margin={{ top: 10, right: 10, left: -20, bottom: 5 }}
                  title="Combined blockers over time trend chart"
                  desc="Line chart showing the combined blocker count of this tag's audits over time. Switch to the table view for detailed values."
                >
                  <XAxis
                    dataKey="date"
                    type="category"
                    interval="preserveStartEnd"
                    minTickGap={24}
                    tickFormatter={(value: string) =>
                      parseDateKey(value).toLocaleDateString("en-US", { month: "numeric", day: "numeric" })
                    }
                    tickMargin={8}
                  />
                  <YAxis
                    orientation="left"
                    label={{ value: "Blockers", angle: -90, position: "insideLeft" }}
                    tickFormatter={formatAxisNumber}
                  />
                  <Tooltip content={<TagReportTooltip />} />
                  <Line
                    type="monotone"
                    dataKey="blockers"
                    stroke={themeVariables.white}
                    strokeWidth={4}
                    dot={CustomizedDot}
                    activeDot={CustomizedActiveDot}
                    name="Combined blockers"
                    isAnimationActive={false}
                  />
                </LineChart>
              </ResponsiveContainer>
            </Tabs.Content>
            <Tabs.Content value="table">
              <div className={styles["data-table"]}>
                <div className="table-container card-table">
                  <table aria-labelledby="tag-report-chart-heading">
                    <thead>
                      <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Combined Blockers</th>
                        <th scope="col">Scans Completed</th>
                      </tr>
                    </thead>
                    <tbody>
                      {scanDays.length === 0 ? (
                        <tr>
                          <td colSpan={3}>No scans completed in this period.</td>
                        </tr>
                      ) : (
                        scanDays.map((point) => (
                          <tr key={point.date}>
                            <td>
                              {parseDateKey(point.date).toLocaleDateString("en-US", { weekday: "short", year: "numeric", month: "short", day: "numeric" })}
                            </td>
                            <td>{point.blockers.toLocaleString()}</td>
                            <td>{point.scansCompleted.toLocaleString()}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </Tabs.Content>
          </Tabs.Root>
        </div>
      )}
    </Card>
  );
};
