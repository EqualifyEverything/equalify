import {
  ColumnDef,
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  RowSelectionState,
  useReactTable,
} from "@tanstack/react-table";
import { useEffect, useMemo, useState } from "react";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { StyledButton } from "./StyledButton";
import styles from "./AuditPagesInputTable.module.scss";
import * as VisuallyHidden from "@radix-ui/react-visually-hidden";

interface ChildProps {
  pages: Page[];
  isShared: boolean;
  removePages: (pagesToRemove: Page[]) => void;
  updatePageType: (url: string, type: "html" | "pdf") => void;
}

interface Page {
  url: string;
  type: "html" | "pdf";
  id?: string;
}

export const AuditPagesInputTable = ({
  pages,
  removePages,
  isShared,
  updatePageType,
}: ChildProps) => {
  const [pagination, setPagination] = useState({
    pageIndex: 0,
    pageSize: 10,
  });
  const [rowSelection, setRowSelection] = useState<RowSelectionState>({});

  useEffect(() => {
    //console.log(rowSelection);
  }, [rowSelection]);

  const columns = useMemo<ColumnDef<Page>[]>(
    () => [
      {
        id: "select-col",
        header: ({ table }) => (
          <>
            <VisuallyHidden.Root>
              <label htmlFor="AuditPagesInputTableSelectAll">
                Select All Rows
              </label>
            </VisuallyHidden.Root>
            <input
              id="AuditPagesInputTableSelectAll"
              type="checkbox"
              checked={table.getIsAllRowsSelected()}
              //indeterminate={table.getIsSomeRowsSelected()}
              onChange={table.getToggleAllRowsSelectedHandler()}
            />
          </>
        ),
        cell: ({ row }) => (
          <>
            <VisuallyHidden.Root>
              <label htmlFor={`AuditPagesInputTableSelect_${row.id}`}>Select Row</label>
            </VisuallyHidden.Root>
            <input
              type="checkbox"
              id={`AuditPagesInputTableSelect_${row.id}`}
              checked={row.getIsSelected()}
              disabled={!row.getCanSelect()}
              onChange={row.getToggleSelectedHandler()}
            />
          </>
        ),
      },
      {
        accessorKey: "url",
        header: "URL",
        cell: ({ getValue }) => {
          const url = getValue() as string;
          return <span>{url}</span>;
        },
      },
      {
        accessorKey: "type",
        header: "Type",
        cell: ({ cell, getValue }) => {
          const url = cell.row.original.url;
          const type = getValue() as string;
          return (
            <select
              name={`pageType_${url}`}
              value={type}
              onChange={(e) =>
                updatePageType(url, e.target.value as "html" | "pdf")
              }
              disabled={isShared}
            >
              <option value="html">HTML</option>
              <option value="pdf">PDF</option>
            </select>
          );
        },
      },
    ],
    [pages]
  );
  const table = useReactTable({
    data: pages || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
      pagination,
      rowSelection,
    },
    onRowSelectionChange: setRowSelection,
    onPaginationChange: setPagination,
  });

  return (
    <>
      {/* {pages.length > 0 ? ( */}
      <div className={"table-container " + styles.AuditPagesInputTable}>
        <table aria-label="Users table">
          <thead>
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id} className="bg-gray-100">
                {headerGroup.headers.map((header) => (
                  <th key={header.id} scope="col">
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
                <td colSpan={columns.length}>No URLs found</td>
              </tr>
            ) : (
              table.getRowModel().rows.map((row, index) => (
                <tr key={row.id}>
                  {row.getVisibleCells().map((cell) => (
                    <td key={cell.id}>
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
        <div className="pagination" role="navigation" aria-label="Pagination">
          <div className="pagination-text">
            {/* Showing {table.getState().pagination.pageSize} of{" "}
              {pages.length} URLs */}
            {!isShared && Object.values(rowSelection).length > 0 ? (
              <StyledButton
                label={`Remove ${Object.values(rowSelection).length} URL(s)`}
                onClick={(e) => {
                  e.preventDefault();
                  removePages(
                    table
                      .getSelectedRowModel()
                      .flatRows.map((row) => row.original)
                  );
                  table.toggleAllPageRowsSelected(false);
                }}
              />
            ) : null}
          </div>
          <div className="pagination-buttons">
            {pages &&
              ` Page ${table.getState().pagination.pageIndex + 1} of ${table.getPageCount()}`}

            <StyledLabeledInput>
              <label htmlFor="pageSize">URLs per page:</label>
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
      {/* ) : (
        <div className="text-center py-8">Loading URLs...</div>
      )} */}
    </>
  );
};
