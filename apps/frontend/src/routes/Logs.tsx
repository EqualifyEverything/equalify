import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import getLogsResponse from "../../../../shared/types/logs";
import getLogsResponseLog from "../../../../shared/types/logs";
import { ChangeEvent, useMemo, useState } from "react";
import {
  ColumnDef,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { SkeletonTable } from "#src/components/Skeleton.tsx";
import { StyledButton } from "#src/components/StyledButton.tsx";
import style from './Logs.module.scss';

export const Logs = () => {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(50);

  const { data: logs, isLoading, isError } = useQuery({
    queryKey: ["logs"],
    queryFn: async () => {
      return (await (
        await API.get({
          apiName: "auth",
          path: "/getLogs",
          options: { queryParams: { page: page.toString(), pageSize: pageSize.toString() } },
        }).response
      ).body.json()) as unknown as getLogsResponse;
    },
    refetchInterval: 5000,
    placeholderData: (previousData) => previousData,
  });

  //console.log(logs);

  interface userObj {
    name:string,
    email:string
  }

  const columns = useMemo<ColumnDef<getLogsResponseLog>[]>(
    () => [
      {
        accessorKey: "created_at",
        header: "Date",
        cell: ({ getValue }) => {
          const theDate = getValue() as string;
          return new Date(theDate).toLocaleString("en-US", {
            dateStyle: 'short'
          }) + " " +
          new Date(theDate).toLocaleString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
          });
        },
      },
      
      {
        accessorKey: "LogToUser",
        header: "User",
        cell: ({ getValue }) => {
            const userObj = getValue() as userObj;
            return `${userObj.name} (${userObj.email})`;
        }
      },
      {
        accessorKey: "message",
        header: "Message",
        meta: {
          className: style["message"]
        },
        cell: ({ getValue }) => {
          return getValue();
        },
      },
      {
        accessorKey: "LogToAudit",
        header: "Audit",
        cell: ({ getValue }) => {
            const userObj = getValue() as { name:string } ?? null;
            return userObj && userObj.name ? userObj.name : "N/A";
        }
      }
    ],
    []
  );
  const table = useReactTable({
    data: logs?.logs || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: logs ? Math.ceil(logs?.logs_aggregate?.aggregate?.count / pageSize) : 0,
  });

  const handlePageSizeChange = (e: ChangeEvent<HTMLSelectElement>)=>{
      setPageSize(parseInt(e.target.value))
    }

  return (
    <>
      <h1 className="initial-focus-element pb-3">Logs</h1>
      {isError && (
        <div className="text-center py-8">Error Loading Logs Data</div>
      )}
      {isLoading ? (
        <SkeletonTable columns={4} rows={8} headers={["Date", "Message", "User", "Audit"]} />
      ) : (
        <>
          <div className="table-container" style={{ marginBottom : "16px" }}>
            <div className="table-scroll-wrapper">
            <table
              aria-label="Logs table"
            >
              <thead>
                {table.getHeaderGroups().map((headerGroup) => (
                  <tr key={headerGroup.id} className="bg-gray-100">
                    {headerGroup.headers.map((header) => (
                      <th
                        key={header.id}
                        scope="col"
                        className="border border-gray-300 px-4 py-2 text-left font-semibold"
                      >
                        {header.isPlaceholder
                          ? null
                          : flexRender(
                              header.column.columnDef.header,
                              header.getContext()
                            )}
                      </th>
                    ))}
                  </tr>
                ))}
              </thead>
              <tbody>
                {table.getRowModel().rows.length === 0 ? (
                  <tr>
                    <td
                      colSpan={columns.length}
                    >
                      No logs found
                    </td>
                  </tr>
                ) : (
                  table.getRowModel().rows.map((row, index) => (
                    <tr
                      key={row.id}
                      >
                      {row.getVisibleCells().map((cell) => (
                        <td
                          key={cell.id}
                          className={cell.column.columnDef.meta?.className ?? ""}
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
                Showing {logs?.logs?.length || 0} of{" "}
                {logs?.logs_aggregate.aggregate.count || 0} logs
                {logs?.logs_aggregate.aggregate.count &&
                  ` (Page ${page + 1} of ${Math.ceil(logs.logs_aggregate.aggregate.count / pageSize)})`}
              </div>
              <div className="pagination-buttons">
                <div className="page-size-control">
                  <label htmlFor="pageSize">Logs per page:</label>
                  <select id="pageSize" value={pageSize} onChange={handlePageSizeChange}>
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>
                <div className="pagination-buttons-prev-next">
                  <StyledButton
                    onClick={() => setPage(0)}
                    disabled={page === 0}
                    label="First"
                    variant="light"
                    className="pagination-edge"
                  />
                  <StyledButton
                    onClick={() => setPage((p) => Math.max(0, p - 1))}
                    disabled={page === 0}
                    label="Previous"
                    variant="light"
                  />
                  <StyledButton
                    onClick={() => setPage((p) => p + 1)}
                    disabled={
                      !logs || page >= Math.ceil(logs.logs_aggregate.aggregate.count / pageSize) - 1
                    }
                    label="Next"
                    variant="light"
                  />
                  <StyledButton
                    onClick={() =>
                      logs && setPage(Math.ceil(logs.logs_aggregate.aggregate.count / pageSize) - 1)
                    }
                    disabled={
                      !logs || page >= Math.ceil(logs.logs_aggregate.aggregate.count / pageSize) - 1
                    }
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
    </>
  );
};
