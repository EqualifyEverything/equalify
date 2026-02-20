import { useQuery } from "@tanstack/react-query";
import { formatId, useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { Link, useNavigate } from "react-router-dom";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { LuClipboard, LuClipboardPlus, LuZap } from "react-icons/lu";
import { Card } from "#src/components/Card.tsx";
const apiClient = API.generateClient();
import styles from "./Audits.module.scss";
import { DataRow } from "#src/components/DataRow.tsx";
import { SkeletonAuditGrid } from "#src/components/Skeleton.tsx";
import { AuditsTable } from "#src/components/AuditsTable.tsx";
import * as Tabs from "@radix-ui/react-tabs";
import { FaTable, FaTableList } from "react-icons/fa6";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { ChangeEvent, useState } from "react";
import { useUser } from "../queries";
import { TbHelpCircleFilled } from "react-icons/tb";

export interface Scan {
  blockers_aggregate: {
    aggregate: {
      count: number;
    };
  };
  percentage: number;
  updated_at: string;
}
/* 
export interface Audit {
  created_at: string;
  id: string;
  interval: string;
  name: string;
  scans: Scan[];
  urls_aggregate: {
    aggregate: {
      count: number;
    };
  };
} */

export const Audits = () => {
  const navigate = useNavigate();
  const { data: user } = useUser();
  const {
    auditsTableView,
    setAuditsTableView,
    auditsTableCreatedByView,
    setAuditsTableCreatedByView
  } = useGlobalStore();
  const [searchString, setSearchString] = useState("");
  const { data: audits, isLoading } = useQuery({
    queryKey: ["audits"],
    queryFn: async () =>
      (
        await (apiClient.graphql({
          //query: `{audits(order_by: {created_at: desc}) {id created_at name}}`,
          query: `
              {
                audits(order_by: {created_at: desc}, where: {interval: {_neq: "Quick Scan"}}) {
                  id
                  created_at
                  name
                  interval
                  user {
                    name
                    email
                  }
                  scans(order_by: {created_at: desc}, limit: 1) {
                    blockers_aggregate(order_by: {created_at: desc}, where: {}) {
                        aggregate {
                          count
                        }
                    }
                    percentage
                    updated_at
                  }
                  urls_aggregate {
                    aggregate {
                        count
                    }
                  }
                }
              }
            `,
        })
        ) as any).data?.audits,
    select: (audits) => {
      let filtered = audits;
      if (auditsTableCreatedByView == "user") {
        filtered = audits.filter((audit: any) => audit.user.email === user.email);
      }
      if (searchString.length >= 3) {
        filtered = filtered.filter((audit: any) => audit.name.includes(searchString))
      }
      return filtered;
    }

  });
  //console.log(audits);

  const handleCreatedByViewChange = (e: ChangeEvent<HTMLSelectElement>) => {
    setAuditsTableCreatedByView(e.target.value);
  };

  return (
    <div className={styles.Audits}>
      <div>
        <div className={styles["audits-header"]}>
          <h1 className="initial-focus-element">Audits</h1>

          <div style={{ display: 'flex', gap: '8px' }}>
            <StyledButton
              onClick={() => navigate("/quick-scans")}
              label="Quick Scan"
              variant="light"
              icon={<LuZap className="icon-small" />}
            />
            <StyledButton
              onClick={() => navigate("/audits/build")}
              label="Add Audit"
              variant="dark"
              icon={<LuClipboardPlus className="icon-small" />}
            />
          </div>
        </div>
      </div>
      <>

        <Tabs.Root
          orientation="horizontal"
          className="audit-tabs"
          value={auditsTableView}
          onValueChange={(value) => setAuditsTableView(value)}
          activationMode="manual"
        >
          <div className={styles["filter-controls"]}>
            <div className={styles["filter-controls-left"]}>
              <StyledLabeledInput>
                <label htmlFor="auditsSearch">Search</label>
                <input id="auditsSearch" type="text" value={searchString} onChange={(e) => setSearchString(e.target.value)}></input>
              </StyledLabeledInput>
              <StyledLabeledInput>
                <label htmlFor="createdByView">Filter View:</label>
                <select onChange={handleCreatedByViewChange} value={auditsTableCreatedByView} id="createdByView">
                  <option value="user">My Audits</option>
                  <option value="all">All Audits</option>
                </select>
              </StyledLabeledInput>
            </div>

            <Tabs.List aria-label="Select a View" className={styles["audits-view-selector"]}>
              <Tabs.Trigger value="cards" className={styles["audits-view-trigger"]} asChild>
                <StyledButton
                  icon={<FaTable />}
                  onClick={() => { }}
                  label={"Cards View"}
                  showLabel={false}
                />
              </Tabs.Trigger>
              <Tabs.Trigger value="table" className={styles["audits-view-trigger"]} asChild>
                <StyledButton
                  icon={<FaTableList />}
                  onClick={() => { }}
                  label={"Cards View"}
                  showLabel={false}
                />
              </Tabs.Trigger>
            </Tabs.List>
          </div>
          <Tabs.Content value="cards">
            <div className="cards-33">
              {isLoading ? (
                <SkeletonAuditGrid count={6} />
              ) : (
                audits?.map((row: any, index: number) => (
                  <Card variant="light" key={index}>
                    <Link
                      to={`/audits/${formatId(row.id)}`}
                    >
                      <h2>
                        <LuClipboard className="icon-small" />
                        {row.name}
                      </h2>
                    </Link>
                    <div className={styles["dataRow-list"]}>
                      <DataRow
                        variant="highlight"
                        the_key="Blockers"
                        the_value={
                          row.scans[0]?.blockers_aggregate?.aggregate?.count ??
                          "—"
                        }
                      />
                      <DataRow
                        the_key="Created By"
                        the_value={<Link to={`mailto:${row.user.email}`}>{row.user.name}</Link>}
                      />
                      <DataRow
                        the_key="URLs"
                        the_value={row.urls_aggregate.aggregate.count}
                      />
                      <DataRow the_key="Runs" the_value={row.interval} />
                      <DataRow
                        the_key="Last Scan"
                        the_value={
                          row.scans[0]
                            ? prettyDate(row.scans[0].updated_at) +
                            " at " +
                            prettyTime(row.scans[0].updated_at)
                            : "Not scanned yet"
                        }
                      />
                      <DataRow
                        variant="no-border"
                        the_key="Created"
                        the_value={prettyDate(row.created_at)}
                      />

                      {/* {row.scans[0].percentage}% */}
                    </div>
                  </Card>
                ))
              )}
              {audits && audits.length == 0 && !isLoading && searchString == "" && <>
                <Card variant="empty-state-card">
                  <TbHelpCircleFilled />
                  <div className="text">
                    <h2>It looks like you haven’t created any accessibility audits yet.</h2>
                    <p>Jump right in by <Link to="build/">creating your first audit</Link> or explore <a style={{ cursor: "pointer" }} onClick={() => setAuditsTableCreatedByView("all")}>existing reports</a> from across your organization to see what’s already being improved.</p>
                  </div>
                </Card>
              </>}
            </div>
          </Tabs.Content>
          <Tabs.Content value="table">
            <AuditsTable audits={audits} isLoading={isLoading} />
          </Tabs.Content>
        </Tabs.Root>
      </>
    </div>
  );
};

function prettyDate(dateTime: string) {
  return new Date(dateTime).toLocaleDateString("en-US", {
    weekday: "short",
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function prettyTime(dateTime: string) {
  const time = new Date(dateTime);
  return time.toLocaleTimeString(navigator.language, {
    hour: "2-digit",
    minute: "2-digit",
  });
}
