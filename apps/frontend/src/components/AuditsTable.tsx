import styles from "./AuditsTable.module.scss";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  ColumnDef,
  getPaginationRowModel,
  SortingState,
  getSortedRowModel,
  SortDirection
} from "@tanstack/react-table";
import { SkeletonTable } from "./Skeleton";
import { StyledButton } from "./StyledButton";
import { useMemo, useState } from "react";
import { Scan } from "#src/routes/Audits.tsx";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { formatId } from "../utils";
import { Link } from "react-router-dom";
import { FaArrowDown, FaArrowUp } from "react-icons/fa";
import { GrPowerCycle } from "react-icons/gr";
import React from "react";

interface Audit {
  created_at: string;
  id: string;
  interval: string;
  name: string;
  scans: Scan[];
  user: {
    name: string;
    email: string;
  }
  urls_aggregate: {
    aggregate: {
      count: number;
    };
  };
}

interface auditsTableProps {
  audits: Audit[];
  isLoading: boolean;
}

export const AuditsTable = ({ audits, isLoading }: auditsTableProps) => {
  //console.log(audits);
  const [pagination, setPagination] = useState({
    pageIndex: 0,
    pageSize: 10,
  });

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
        header: "Status",
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
        header: "Name",
        cell: ({ row }) => {
          return <Link to={`/audits/${formatId(row.original.id)}`} className={styles["audit-name"]}>{row.original.name}</Link>;
        },
      },

      {
        accessorKey: "user",
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
        accessorKey: "interval",
        header: "Runs",
        cell: ({ getValue }) => {
          const interval = getValue() as any;
          return interval;
        },
      },
      {
        accessorKey: "created_at",
        header: "Created",
        cell: ({ getValue }) => {
          const created = getValue() as string;
          return shortDate(created);
        },
      },
      {
        accessorKey: "scans",
        id: "lastScan",
        header: "Last Scan",
        cell: ({ getValue }) => {
          const scans = getValue() as Scan[];
          if (!scans || scans.length == 0 || !scans[0].updated_at) return "N/A";
          return shortDate(scans[0].updated_at);
        },
      },
      {
        accessorFn: (row) => row.urls_aggregate.aggregate.count,
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

  const [sorting, setSorting] = React.useState<SortingState>([]);
  const table = useReactTable({
    data: audits || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination
    },
    state: {
      sorting
    },
    onSortingChange: setSorting,
    getSortedRowModel: getSortedRowModel(),
  });

  return (
    <div className={styles.AuditsTable}>
      {isLoading ? (
        <SkeletonTable
          columns={3}
          rows={6}
          headers={["Email", "Created At", "Actions"]}
        />
      ) : (
        <>
          <div className="table-container">
            <table aria-label="Audits table">
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
                          <button className={styles["header-sort"]} aria-label={`Sort by "${header.column.columnDef.header}" ${header.column.getIsSorted() === 'asc' ? 'descending' : 'ascending'
                            }`}>
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

            {/* Pagination Controls */}
            <div
              className="pagination"
              role="navigation"
              aria-label="Pagination"
            >
              <div className="pagination-text">
                Showing {table.getState().pagination.pageSize} of{" "}
                {audits.length} Audits
              </div>
              <div className="pagination-buttons">
                {audits &&
                  ` Page ${table.getState().pagination.pageIndex + 1} of ${table.getPageCount()}`}

                <StyledLabeledInput>
                  <label htmlFor="pageSize">Audits per page:</label>
                  <select
                    id="pageSize"
                    value={table.getState().pagination.pageSize}
                    onChange={(e) => {
                      table.setPageSize(Number(e.target.value));
                    }}
                  >
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </StyledLabeledInput>

                <button
                  onClick={() => table.firstPage()}
                  disabled={table.getState().pagination.pageIndex == 0}
                  aria-label="Go to first page"
                >
                  First
                </button>
                <button
                  onClick={() => table.previousPage()}
                  disabled={!table.getCanPreviousPage()}
                  aria-label="Go to previous page"
                >
                  Previous
                </button>
                <button
                  onClick={() => table.nextPage()}
                  disabled={!table.getCanNextPage()}
                  aria-label="Go to next page"
                >
                  Next
                </button>
                <button
                  onClick={() => table.lastPage()}
                  disabled={
                    table.getState().pagination.pageIndex + 1 >=
                    table.getPageCount()
                  }
                  aria-label="Go to last page"
                >
                  Last
                </button>
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
