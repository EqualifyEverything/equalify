import { useQuery } from "@tanstack/react-query";
import { formatId, useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { Link, useNavigate } from "react-router-dom";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { LuClipboard, LuClipboardPlus, LuZap } from "react-icons/lu";
import { GrPowerCycle } from "react-icons/gr";
import { Card } from "#src/components/Card.tsx";
const apiClient = API.generateClient();
import styles from "./Audits.module.scss";
import { DataRow } from "#src/components/DataRow.tsx";
import { SkeletonAuditGrid } from "#src/components/Skeleton.tsx";
import { AuditsTable } from "#src/components/AuditsTable.tsx";
import * as Tabs from "@radix-ui/react-tabs";
import { FaTable, FaTableList } from "react-icons/fa6";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { ChangeEvent, useEffect, useRef, useState } from "react";
import { tagsFromAssignments, useAuditTags, useUser } from "../queries";
import Select, { MultiValue } from "react-select";
import { useSelectStyles } from "../utils/useSelectStyles";
import { AuditTagList } from "#src/components/AuditTagList.tsx";
import { TbHelpCircleFilled } from "react-icons/tb";
import { WhatsNew } from "#src/components/WhatsNew.tsx";

export interface Scan {
  blockers_aggregate: {
    aggregate: {
      count: number;
    };
  };
  percentage: number;
  updated_at: string;
  status: string;
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

// Cards sit three across, so every page size fills whole rows
const CARD_PAGE_SIZES = [15, 30, 60, 90];

export const Audits = () => {
  const navigate = useNavigate();
  const { data: user } = useUser();
  const {
    auditsTableView,
    setAuditsTableView,
    auditsTableCreatedByView,
    setAuditsTableCreatedByView,
    auditsTableTagFilter,
    setAuditsTableTagFilter,
    setAnnounceMessage,
  } = useGlobalStore();
  const [searchString, setSearchString] = useState("");
  // Cards view paging. The table view pages itself (AuditsTable).
  const [cardPage, setCardPage] = useState(0);
  const [cardPageSize, setCardPageSize] = useState(CARD_PAGE_SIZES[0]);
  const cardsRef = useRef<HTMLDivElement>(null);
  const cardsPagerRef = useRef<HTMLDivElement>(null);
  // Set by the pager buttons so the effect below knows a person changed the
  // page (and which way), as opposed to a filter or refetch moving it
  const cardPageMove = useRef<{ forward: boolean; byPointer: boolean } | null>(null);
  const selectStyles = useSelectStyles();
  const { data: allTags } = useAuditTags();
  const tagOptions = (allTags ?? []).map((tag) => ({ value: tag.id, label: tag.name }));
  // Ignore remembered tags that have since been deleted or merged away
  const activeTagFilter = allTags
    ? auditsTableTagFilter.filter((id) => allTags.some((tag) => tag.id === id))
    : [];
  const { data: audits, isLoading, isError } = useQuery({
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
                    status
                    percentage
                    updated_at
                  }
                  urls_aggregate {
                    aggregate {
                        count
                    }
                  }
                  tag_assignments(order_by: {tag: {name: asc}}) {
                    tag {
                      id
                      name
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
        filtered = filtered.filter((audit: any) => audit.name.toLowerCase().includes(searchString.toLowerCase()))
      }
      if (activeTagFilter.length) {
        filtered = filtered.filter((audit: any) =>
          audit.tag_assignments.some((assignment: any) => activeTagFilter.includes(assignment.tag.id))
        );
      }
      return filtered;
    }

  });
  //console.log(audits);

  // The list is already filtered and fully loaded; paging only decides which
  // cards are on screen. Clamp rather than trust cardPage, since a refetch or
  // filter can leave it past the end.
  const auditCount = audits?.length ?? 0;
  const cardPageCount = Math.max(1, Math.ceil(auditCount / cardPageSize));
  const currentCardPage = Math.min(cardPage, cardPageCount - 1);
  const firstCardIndex = currentCardPage * cardPageSize;
  const visibleCards = audits?.slice(firstCardIndex, firstCardIndex + cardPageSize);
  const lastCardNumber = Math.min(firstCardIndex + cardPageSize, auditCount);

  // A new search or filter starts again from the first page. Search only
  // filters from three characters, so shorter input doesn't count as a change.
  const tagFilterKey = activeTagFilter.join(",");
  const effectiveSearch = searchString.length >= 3 ? searchString.toLowerCase() : "";
  useEffect(() => {
    setCardPage(0);
  }, [effectiveSearch, auditsTableCreatedByView, tagFilterKey]);

  const goToCardPage = (page: number, e?: { detail?: number }) => {
    // detail is 0 when a button is activated from the keyboard
    cardPageMove.current = { forward: page > currentCardPage, byPointer: (e?.detail ?? 0) > 0 };
    setCardPage(page);
  };

  // After a person changes page: tell screen readers what is showing, bring
  // the first cards into view for pointer users, and keep focus in the pager
  // if the button that was pressed has just been disabled (first or last
  // page), which would otherwise drop focus to the top of the document.
  useEffect(() => {
    const move = cardPageMove.current;
    if (!move) return;
    cardPageMove.current = null;
    setAnnounceMessage(
      `Showing audits ${firstCardIndex + 1} to ${lastCardNumber} of ${auditCount}, page ${currentCardPage + 1} of ${cardPageCount}`,
      "normal",
      true
    );
    if (move.byPointer) cardsRef.current?.scrollIntoView({ block: "start" });
    const active = document.activeElement as HTMLButtonElement | null;
    if (!active || active === document.body || active.disabled) {
      cardsPagerRef.current
        ?.querySelector<HTMLButtonElement>(move.forward ? ".cards-pager-previous" : ".cards-pager-next")
        // After a click the view has just moved to the first cards: don't pull it back down
        ?.focus({ preventScroll: move.byPointer });
    }
  }, [currentCardPage]);

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
      <WhatsNew />
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
              {tagOptions.length > 0 && (
                <StyledLabeledInput className={styles["tag-filter"]}>
                  <label htmlFor="auditsTagFilter">Filter by Tags:</label>
                  <Select
                    inputId="auditsTagFilter"
                    className="react-select"
                    isMulti
                    options={tagOptions}
                    value={tagOptions.filter((option) => activeTagFilter.includes(option.value))}
                    onChange={(selected: MultiValue<{ value: string; label: string }>) =>
                      setAuditsTableTagFilter(selected.map((option) => option.value))
                    }
                    placeholder="Any tag..."
                    styles={selectStyles}
                  />
                </StyledLabeledInput>
              )}
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
                  label={"Table View"}
                  showLabel={false}
                />
              </Tabs.Trigger>
            </Tabs.List>
          </div>
          {isError && (
            <Card variant="short-error">
              <TbHelpCircleFilled className="icon-small" />
              <div className="font-small">
                <b>Unable to load audits.</b> Please refresh the page, or contact your administrator if this keeps happening.
              </div>
            </Card>
          )}
          <Tabs.Content value="cards">
            <div className="cards-33" ref={cardsRef}>
              {isLoading ? (
                <SkeletonAuditGrid count={6} />
              ) : (
                visibleCards?.map((row: any) => (
                  // Keyed by audit, not position, so a card's own state (expanded
                  // tags) doesn't carry over to a different audit on the next page
                  <Card variant="light" key={row.id}>
                    <Link
                      to={`/audits/${formatId(row.id)}`}
                    >
                      <h2>
                        <LuClipboard className="icon-small" />
                        {row.name}
                        {row.scans[0]?.status === "processing" && (
                          <span role="img" aria-label="Processing" className={styles["processing-icon"]}>
                            <GrPowerCycle className={`icon-small ${styles.spinning}`} />
                          </span>
                        )}
                      </h2>
                    </Link>
                    <AuditTagList tags={tagsFromAssignments(row.tag_assignments)} maxVisible={3} className={styles["card-tags"]} linkToReport toggleAlign="end" />
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
              {audits && audits.length == 0 && !isLoading && searchString == "" && activeTagFilter.length > 0 && (
                <Card variant="empty-state-card">
                  <TbHelpCircleFilled />
                  <div className="text">
                    <h2>No audits match the selected tags.</h2>
                    <StyledButton variant="light" label="Clear Tag Filter" onClick={() => setAuditsTableTagFilter([])} />
                  </div>
                </Card>
              )}
              {audits && audits.length == 0 && !isLoading && searchString == "" && activeTagFilter.length == 0 && <>
                <Card variant="empty-state-card">
                  <TbHelpCircleFilled />
                  <div className="text">
                    <h2>It looks like you haven’t created any accessibility audits yet.</h2>
                    <p>Jump right in by <Link to="build/">creating your first audit</Link> or explore <a style={{ cursor: "pointer" }} onClick={() => setAuditsTableCreatedByView("all")}>existing reports</a> from across your organization to see what’s already being improved.</p>
                  </div>
                </Card>
              </>}
            </div>
            {/* Paging controls, shown once there are more audits than the smallest page holds */}
            {!isLoading && auditCount > CARD_PAGE_SIZES[0] && (
              <div className={"table-container " + styles["cards-pagination"]}>
                <div className="pagination" role="navigation" aria-label="Audit cards pagination" ref={cardsPagerRef}>
                  <div className="pagination-text">
                    Showing {firstCardIndex + 1}–{lastCardNumber} of {auditCount} audits (Page {currentCardPage + 1} of {cardPageCount})
                  </div>
                  <div className="pagination-buttons">
                    <div className="page-size-control">
                      <label htmlFor="cardsPageSize">Audits per page:</label>
                      <select
                        id="cardsPageSize"
                        value={cardPageSize}
                        onChange={(e) => {
                          const size = Number(e.target.value);
                          // Stay on the page that holds the first card now showing
                          setCardPage(Math.floor(firstCardIndex / size));
                          setCardPageSize(size);
                        }}
                      >
                        {CARD_PAGE_SIZES.map((size) => (
                          <option key={size} value={size}>{size}</option>
                        ))}
                      </select>
                    </div>
                    <div className="pagination-buttons-prev-next">
                      <StyledButton
                        onClick={(e) => goToCardPage(0, e)}
                        disabled={currentCardPage === 0}
                        label="First"
                        variant="light"
                        className="pagination-edge"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(currentCardPage - 1, e)}
                        disabled={currentCardPage === 0}
                        label="Previous"
                        variant="light"
                        className="cards-pager-previous"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(currentCardPage + 1, e)}
                        disabled={currentCardPage >= cardPageCount - 1}
                        label="Next"
                        variant="light"
                        className="cards-pager-next"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(cardPageCount - 1, e)}
                        disabled={currentCardPage >= cardPageCount - 1}
                        label="Last"
                        variant="light"
                        className="pagination-edge"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}
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
