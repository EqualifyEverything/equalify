import { formatId, useDebounce, useGlobalStore } from "../utils";
import { Link, useNavigate } from "react-router-dom";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { LuClipboard, LuClipboardPlus, LuZap } from "react-icons/lu";
import { GrPowerCycle } from "react-icons/gr";
import { Card } from "#src/components/Card.tsx";
import styles from "./Audits.module.scss";
import { DataRow } from "#src/components/DataRow.tsx";
import { SkeletonAuditGrid } from "#src/components/Skeleton.tsx";
import { AuditsTable } from "#src/components/AuditsTable.tsx";
import * as Tabs from "@radix-ui/react-tabs";
import { FaTable, FaTableList } from "react-icons/fa6";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { ChangeEvent, useEffect, useRef, useState } from "react";
import { tagsFromAssignments, useAuditTags } from "../queries";
import { useAuditsList } from "#src/queries/useAuditsList.ts";
import Select, { MultiValue } from "react-select";
import { useSelectStyles } from "../utils/useSelectStyles";
import { AuditTagList } from "#src/components/AuditTagList.tsx";
import { TbHelpCircleFilled } from "react-icons/tb";
import { WhatsNew } from "#src/components/WhatsNew.tsx";

// Cards sit three across, so every page size fills whole rows
const CARD_PAGE_SIZES = [15, 30, 60, 90];

// Wait for a pause in typing before asking the backend to search
const SEARCH_DELAY_MS = 300;

export const Audits = () => {
  const navigate = useNavigate();
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
  const cardsRef = useRef<HTMLDivElement>(null);
  const cardsPagerRef = useRef<HTMLDivElement>(null);
  // Set by the pager buttons so the effect below knows a person changed the
  // page (to where, which way, and with which button), as opposed to a filter
  // or refetch moving it
  const cardPageMove = useRef<{ page: number; forward: boolean; byPointer: boolean; button: HTMLButtonElement | null } | null>(null);
  const selectStyles = useSelectStyles();
  const { data: allTags, isLoading: tagsLoading } = useAuditTags();
  const tagOptions = (allTags ?? []).map((tag) => ({ value: tag.id, label: tag.name }));
  // Ignore remembered tags that have since been deleted or merged away
  const activeTagFilter = allTags
    ? auditsTableTagFilter.filter((id) => allTags.some((tag) => tag.id === id))
    : [];
  // Search only applies from three characters, so shorter input isn't a search
  // yet. Lower-cased because the search ignores case anyway.
  const search: string = useDebounce(searchString.length >= 3 ? searchString.toLowerCase() : "", SEARCH_DELAY_MS);
  // A remembered tag filter can't be applied until the tags have loaded, so
  // hold the list until then rather than load it unfiltered first
  const filtersReady = !(auditsTableTagFilter.length > 0 && tagsLoading);
  const listFilters = { search, createdBy: auditsTableCreatedByView, tagIds: activeTagFilter };

  // The cards view's audits, one page at a time from the backend, newest
  // first. The table view loads and pages its own (AuditsTable), so only the
  // view on screen asks for anything.
  const list = useAuditsList({
    ...listFilters,
    defaultPageSize: CARD_PAGE_SIZES[0],
    enabled: auditsTableView === "cards" && filtersReady,
  });
  const auditCount = list.totalCount;
  // What the pager says describes the cards on screen. While the next page
  // loads, that is still the previous one.
  const shownPage = list.shown?.page ?? 0;
  const shownPageCount = list.shown?.totalPages ?? 1;
  const firstCardIndex = shownPage * (list.shown?.pageSize ?? list.pageSize);
  const lastCardNumber = firstCardIndex + (list.audits?.length ?? 0);
  // Nothing to show, as opposed to nothing loaded yet
  const noAudits = list.settled && auditCount === 0 && searchString == "" && search == "";

  const goToCardPage = (page: number, e?: { detail?: number; currentTarget?: EventTarget | null }) => {
    const from = list.page;
    if (!list.goToPage(page)) return;
    cardPageMove.current = {
      page,
      forward: page > from,
      // detail is 0 when a button is activated from the keyboard
      byPointer: (e?.detail ?? 0) > 0,
      button: e?.currentTarget instanceof HTMLButtonElement ? e.currentTarget : null,
    };
  };

  // Once the page a person asked for is on screen: tell screen readers what
  // is showing and bring the first cards into view for pointer users. And if
  // the button that was pressed has just been disabled (first or last page
  // reached), move focus to its neighbour, since a disabled button drops
  // focus to the top of the document.
  useEffect(() => {
    const move = cardPageMove.current;
    if (!move) return;
    if (list.isError) {
      cardPageMove.current = null;
      return;
    }
    if (!list.settled) return;
    cardPageMove.current = null;
    // Unless something else moved the list in the meantime (a filter, say),
    // in which case that page turn isn't what is on screen
    if (shownPage === move.page) {
      setAnnounceMessage(
        `Showing audits ${firstCardIndex + 1} to ${lastCardNumber} of ${auditCount}, page ${shownPage + 1} of ${shownPageCount}`,
        "normal",
        true
      );
      if (move.byPointer) cardsRef.current?.scrollIntoView({ block: "start" });
    }
    const active = document.activeElement;
    // Only if focus is still where the press left it: on the button, or
    // nowhere (some browsers don't focus a button when it is clicked)
    if (move.button?.disabled && (!active || active === document.body || active === move.button)) {
      cardsPagerRef.current
        ?.querySelector<HTMLButtonElement>(move.forward ? ".cards-pager-previous" : ".cards-pager-next")
        // After a click the view has just moved to the first cards: don't pull it back down
        ?.focus({ preventScroll: move.byPointer });
    }
  }, [list.audits, list.settled, list.isError]);

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
          {auditsTableView === "cards" && list.isError && (
            <Card variant="short-error">
              <TbHelpCircleFilled className="icon-small" />
              <div className="font-small">
                <b>Unable to load audits.</b> Please refresh the page, or contact your administrator if this keeps happening.
              </div>
            </Card>
          )}
          <Tabs.Content value="cards">
            <div className="cards-33" ref={cardsRef} aria-busy={!list.isPending && !list.settled && !list.isError}>
              {list.isPending ? (
                <SkeletonAuditGrid count={6} />
              ) : (
                list.audits?.map((row) => (
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
              {noAudits && activeTagFilter.length > 0 && (
                <Card variant="empty-state-card">
                  <TbHelpCircleFilled />
                  <div className="text">
                    <h2>No audits match the selected tags.</h2>
                    <StyledButton variant="light" label="Clear Tag Filter" onClick={() => setAuditsTableTagFilter([])} />
                  </div>
                </Card>
              )}
              {noAudits && activeTagFilter.length == 0 && <>
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
            {list.shown && auditCount > CARD_PAGE_SIZES[0] && (
              <div className={"table-container " + styles["cards-pagination"]}>
                <div className="pagination" role="navigation" aria-label="Audit cards pagination" ref={cardsPagerRef}>
                  <div className="pagination-text">
                    Showing {firstCardIndex + 1}–{lastCardNumber} of {auditCount} audits (Page {shownPage + 1} of {shownPageCount})
                  </div>
                  <div className="pagination-buttons">
                    <div className="page-size-control">
                      <label htmlFor="cardsPageSize">Audits per page:</label>
                      <select
                        id="cardsPageSize"
                        value={list.pageSize}
                        onChange={(e) => list.changePageSize(Number(e.target.value))}
                      >
                        {CARD_PAGE_SIZES.map((size) => (
                          <option key={size} value={size}>{size}</option>
                        ))}
                      </select>
                    </div>
                    <div className="pagination-buttons-prev-next">
                      <StyledButton
                        onClick={(e) => goToCardPage(0, e)}
                        disabled={shownPage === 0}
                        label="First"
                        variant="light"
                        className="pagination-edge"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(list.page - 1, e)}
                        disabled={shownPage === 0}
                        label="Previous"
                        variant="light"
                        className="cards-pager-previous"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(list.page + 1, e)}
                        disabled={shownPage >= shownPageCount - 1}
                        label="Next"
                        variant="light"
                        className="cards-pager-next"
                      />
                      <StyledButton
                        onClick={(e) => goToCardPage(list.pageCount - 1, e)}
                        disabled={shownPage >= shownPageCount - 1}
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
            <AuditsTable {...listFilters} enabled={filtersReady} />
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
