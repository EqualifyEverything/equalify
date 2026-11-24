import styles from "./AuditsTable.module.scss";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  Column,
  ColumnDef,
  SortingState,
  SortDirection
} from "@tanstack/react-table";
import { SkeletonTable } from "./Skeleton";
import { StyledButton } from "./StyledButton";
import { useMemo } from "react";

import { formatId } from "../utils";
import { Link } from "react-router-dom";
import { FaArrowDown, FaArrowUp } from "react-icons/fa";
import { GrPowerCycle } from "react-icons/gr";
import React from "react";
import * as VisuallyHidden from "@radix-ui/react-visually-hidden";
import { useScrollFade } from "#src/utils/useScrollFade.ts";
import { tagsFromAssignments } from "#src/queries/useAuditTags.ts";
import { AuditListItem, AuditListScan, useAuditsList } from "#src/queries/useAuditsList.ts";
import { AuditTagList } from "./AuditTagList";
import { Card } from "./Card";
import { TbHelpCircleFilled } from "react-icons/tb";

type Audit = AuditListItem;
type Scan = AuditListScan;

// The Search, Filter View and Filter by Tags controls live on the Audits
// screen; the table loads, sorts and pages the matching audits itself.
interface auditsTableProps {
  search: string;
  createdBy: string;
  tagIds: string[];
  enabled?: boolean;
}

const PAGE_SIZES = [10, 50, 100];

// Stable reference: a fresh `[]` each render makes react-table see new data
// every time and re-render in an endless loop
const NO_AUDITS: Audit[] = [];

export const AuditsTable = ({ search, createdBy, tagIds, enabled = true }: auditsTableProps) => {
  const [scrollFadeRef, showScrollFade] = useScrollFade();

  function renderSortingIcon(val: false | SortDirection) {
    switch (val) {
      case "asc": return <FaArrowUp />;
      case "desc": return <FaArrowDown />;
      default: return false;
    }
  }
  const columns = useMemo<ColumnDef<Audit>[]>(
    () => [
      {
        accessorFn: (row) => (row.scans[0]?.status === "processing" ? 1 : 0),
        id: "status",
        sortDescFirst: true,
        header: () => (
          <>
            <GrPowerCycle aria-hidden="true" />
            <VisuallyHidden.Root>Status</VisuallyHidden.Root>
          </>
        ),
        sortingFn: "basic",
        cell: ({ row }) => {
          if (row.original.scans[0]?.status === "processing") {
            return (
              <span role="img" aria-label="Processing">
                <GrPowerCycle className={styles.spinning} />
              </span>
            );
          }
          return null;
        },
      },
      {
        accessorKey: "name",
        sortDescFirst: false,
        header: "Name",
        cell: ({ row }) => {
          return <Link to={`/audits/${formatId(row.original.id)}`} className={styles["audit-name"]}>{row.original.name}</Link>;
        },
      },

      {
        accessorKey: "user",
        sortDescFirst: false,
        header: "Created By",
        cell: ({ getValue }) => {
          const user = getValue() as any;;
          return <Link to={`mailto:${user.email}`}>{user.name}</Link>;
        },
        sortingFn: (rowA, rowB, columnId) => {
          const valA = rowA.original.user.name;
          const valB = rowB.original.user.name;
          return valA.localeCompare(valB);
        }
      },
      {
        accessorFn: (row) => tagsFromAssignments(row.tag_assignments).map((tag) => tag.name).join(", ") || undefined,
        id: "tags",
        sortDescFirst: false,
        header: "Tags",
        sortUndefined: 'last',
        cell: ({ row }) => <AuditTagList tags={tagsFromAssignments(row.original.tag_assignments)} maxVisible={2} className={styles["tags-cell"]} linkToReport toggleAlign="start" />,
      },
      {
        accessorKey: "interval",
        sortDescFirst: false,
        header: "Runs",
        cell: ({ getValue }) => {
          const interval = getValue() as any;
          return interval;
        },
      },
      {
        accessorKey: "created_at",
        sortDescFirst: false,
        header: "Created",
        cell: ({ getValue }) => {
          const created = getValue() as string;
          return shortDate(created);
        },
      },
      {
        accessorKey: "scans",
        id: "lastScan",
        sortDescFirst: true,
        header: "Last Scan",
        cell: ({ getValue }) => {
          const scans = getValue() as Scan[];
          if (!scans || scans.length == 0 || !scans[0].updated_at) return "N/A";
          return shortDate(scans[0].updated_at);
        },
      },
      {
        accessorFn: (row) => row.urls_aggregate.aggregate.count,
        id: "urls",
        sortDescFirst: true,
        sortUndefined: 'last',
        accessorKey: "urls_aggregate",
        header: "URLs",
        cell: ({ getValue }) => {
          const urls = getValue() as number;
          if (!urls) return <>N/A</>;
          return urls;
        },
      },
      {
        accessorFn: (row) => row.scans[0]?.blockers_aggregate?.aggregate?.count,
        id: "blockers",
        sortDescFirst: true,
        sortUndefined: 'last',
        accessorKey: "scans",
        header: "Blockers",
        cell: ({ getValue }) => {
          const scans = getValue() as number;
          if (!scans) return <>N/A</>;
          return <b>{scans}</b>;
        },
      },
    ],
    []
  );

  // Sorting and paging happen in the backend, across every matching audit:
  // the table only ever holds the page it is showing. Each column's id is the
  // name the backend sorts by (SORT_KEYS in getAudits.ts), one column at a
  // time. No sort means newest first. Each column also states which way it
  // sorts first (sortDescFirst): left to itself the table decides that from
  // the first row on screen, which changes with every sort.
  const [sorting, setSorting] = React.useState<SortingState>([]);
  const sort = sorting[0];
  const list = useAuditsList({
    search,
    createdBy,
    tagIds,
    sortBy: sort?.id,
    sortOrder: sort ? (sort.desc ? "desc" : "asc") : undefined,
    defaultPageSize: PAGE_SIZES[0],
    enabled,
  });
  // The pager describes the rows on screen; while the next page loads, that
  // is still the previous one
  const shownPage = list.shown?.page ?? 0;
  const shownPageCount = list.shown?.totalPages ?? 1;

  const table = useReactTable({
    data: list.audits ?? NO_AUDITS,
    columns,
    // Rows are known by their audit, not their position: each page holds
    // different audits in the same positions, and a row's own state (its
    // expanded tag list) must not carry over to whichever audit comes next
    getRowId: (row) => row.id,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    enableMultiSort: false,
    state: {
      sorting
    },
    onSortingChange: setSorting,
  });

  // What a column's sort button will do when it is next activated, for screen
  // readers: some columns sort descending first, and a third press clears the sort
  const sortButtonLabel = (column: Column<Audit, unknown>) => {
    const name = typeof column.columnDef.header === 'string' ? column.columnDef.header : column.id;
    const next = column.getNextSortingOrder();
    return next
      ? `Sort by "${name}" ${next === 'asc' ? 'ascending' : 'descending'}`
      : `Remove sorting by "${name}"`;
  };

  const loadError = list.isError && (
    <Card variant="short-error">
      <TbHelpCircleFilled className="icon-small" />
      <div className="font-small">
        <b>Unable to load audits.</b> Please refresh the page, or contact your administrator if this keeps happening.
      </div>
    </Card>
  );
  // Nothing loaded to fall back on: the message is all there is to show
  if (list.isError && !list.audits) return loadError;

  return (
    <div className={styles.AuditsTable}>
      {loadError}
      {list.isPending ? (
        <SkeletonTable
          columns={3}
          rows={6}
          headers={["Email", "Created At", "Actions"]}
        />
      ) : (
        <>
          <div className="table-container">
            <div className={"table-scroll-wrapper" + (showScrollFade ? " scroll-fade-active" : "")} ref={scrollFadeRef} tabIndex={0} role="region" aria-label="Audits table, scrollable horizontally">
            <table aria-label="Audits table" aria-busy={!list.settled && !list.isError}>
              <thead>
                {table.getHeaderGroups().map((headerGroup) => (
                  <tr key={headerGroup.id} className="bg-gray-100">
                    {headerGroup.headers.map((header) => (
                      <th key={header.id} scope="col" aria-sort={
                        header.column.getIsSorted() === 'asc'
                          ? 'ascending'
                          : header.column.getIsSorted() === 'desc'
                            ? 'descending'
                            : 'none'
                      }>
                        <div
                          {...{
                            onClick: header.column.getToggleSortingHandler()
                          }}
                        >
                          <button className={styles["header-sort"]} aria-label={sortButtonLabel(header.column)}>
                            <div className={styles["header-label"]}>
                              {flexRender(
                                header.column.columnDef.header,
                                header.getContext()
                              )}
                            </div>
                            {{
                              asc: renderSortingIcon("asc"),
                              desc: renderSortingIcon("desc"),
                            }[header.column.getIsSorted() as string] ?? <div className={styles["arrow-placeholder"]} />}

                          </button>
                        </div>
                      </th>
                    ))}
                  </tr>
                ))}
              </thead>
              <tbody>
                {table.getRowModel().rows.length === 0 ? (
                  <tr>
                    <td colSpan={columns.length}>No audits found</td>
                  </tr>
                ) : (
                  table.getRowModel().rows.map((row, index) => (
                    <tr
                      key={row.id}
                      className={index % 2 === 0 ? "bg-white" : "bg-gray-50"}
                    >
                      {row.getVisibleCells().map((cell) => (
                        <td
                          key={cell.id}
                          className="border border-gray-300 px-4 py-2"
                        >
                          {flexRender(
                            cell.column.columnDef.cell,
                            cell.getContext()
                          )}
                        </td>
                      ))}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
            </div>

            {/* Pagination Controls */}
            <div
              className="pagination"
              role="navigation"
              aria-label="Pagination"
            >
              <div className="pagination-text">
                {list.shown && `Page ${shownPage + 1} of ${shownPageCount}`}
              </div>
              <div className="pagination-buttons">
                <div className="page-size-control">
                  <label htmlFor="pageSize">Audits per page:</label>
                  <select
                    id="pageSize"
                    value={list.pageSize}
                    onChange={(e) => list.changePageSize(Number(e.target.value))}
                  >
                    {PAGE_SIZES.map((size) => (
                      <option key={size} value={size}>{size}</option>
                    ))}
                  </select>
                </div>
                <div className="pagination-buttons-prev-next">
                  <StyledButton
                    onClick={() => { list.goToPage(0); }}
                    disabled={shownPage === 0}
                    label="First"
                    variant="light"
                    className="pagination-edge"
                  />
                  <StyledButton
                    onClick={() => { list.goToPage(list.page - 1); }}
                    disabled={shownPage === 0}
                    label="Previous"
                    variant="light"
                  />
                  <StyledButton
                    onClick={() => { list.goToPage(list.page + 1); }}
                    disabled={shownPage >= shownPageCount - 1}
                    label="Next"
                    variant="light"
                  />
                  <StyledButton
                    onClick={() => { list.goToPage(list.pageCount - 1); }}
                    disabled={shownPage >= shownPageCount - 1}
                    label="Last"
                    variant="light"
                    className="pagination-edge"
                  />
                </div>
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

function shortDate(dateTime: string) {
  return new Date(dateTime).toLocaleDateString("en-US", {
    //weekday: "short",
    year: "numeric",
    month: "numeric",
    day: "numeric",
  });
}
