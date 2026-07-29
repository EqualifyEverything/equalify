import styles from "./InvitesTable.module.scss";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  ColumnDef,
} from "@tanstack/react-table";
import * as API from "aws-amplify/api";
import { useMemo } from "react";
import { useGlobalStore } from "../utils";
import { SkeletonTable } from "./Skeleton";
import { StyledButton } from "./StyledButton";

interface AccessRequest {
  id: string;
  name: string | null;
  email: string;
  status: string;
  created_at: string;
}

export const RequestsTable = () => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();

  const { data, isLoading, error } = useQuery({
    queryKey: ["accessRequests"],
    queryFn: async () => {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/getAccessRequests",
        }).response
      ).body.json()) as any;
      if (response?.status !== "success") {
        throw new Error(response?.message ?? "Failed to load access requests");
      }
      return response.requests as AccessRequest[];
    },
  });

  const reviewRequestMutation = useMutation({
    mutationFn: async ({ id, action }: { id: string; action: "approve" | "deny" }) => {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/reviewAccessRequest",
          options: { body: { id, action } },
        }).response
      ).body.json()) as any;
      if (response?.status !== "success") {
        throw new Error(response?.message ?? "Failed to review request");
      }
      return { action };
    },
    onSuccess: ({ action }) => {
      queryClient.invalidateQueries({ queryKey: ["accessRequests"] });
      queryClient.invalidateQueries({ queryKey: ["invites"] });
      setAnnounceMessage(action === "approve" ? "Request approved — invite created!" : "Request denied", "success");
    },
    onError: (err: Error) => {
      window.alert(err.message);
      setAnnounceMessage(err.message, "error");
    },
  });

  const columns = useMemo<ColumnDef<AccessRequest>[]>(
    () => [
      {
        accessorKey: "name",
        header: "Name",
        cell: ({ getValue }) => {
          const name = getValue() as string | null;
          return <span className="text-sm">{name || "—"}</span>;
        },
      },
      {
        accessorKey: "email",
        header: "Email",
        cell: ({ getValue }) => {
          const email = getValue() as string;
          return <span className="text-sm">{email}</span>;
        },
      },
      {
        accessorKey: "created_at",
        header: "Requested At",
        cell: ({ getValue }) => {
          const date = getValue() as string;
          return (
            <span className="text-sm whitespace-nowrap">
              {new Date(date).toLocaleDateString()}
            </span>
          );
        },
      },
      {
        accessorKey: "id",
        header: "Actions",
        cell: ({ getValue, row }) => {
          const requestId = getValue() as string;
          return (
            <div style={{ display: "flex", gap: "8px", justifyContent: "flex-end" }}>
              <StyledButton
                variant="green"
                label="Approve"
                inline
                onClick={() => reviewRequestMutation.mutate({ id: requestId, action: "approve" })}
                aria-label={`Approve access request from ${row.original.email}`}
                disabled={reviewRequestMutation.isPending}
              />
              <StyledButton
                variant="red"
                label="Deny"
                inline
                onClick={() => {
                  if (confirm(`Deny the access request from ${row.original.email}?`)) {
                    reviewRequestMutation.mutate({ id: requestId, action: "deny" });
                  }
                }}
                aria-label={`Deny access request from ${row.original.email}`}
                disabled={reviewRequestMutation.isPending}
              />
            </div>
          );
        },
      },
    ],
    [reviewRequestMutation]
  );

  const table = useReactTable({
    data: data || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  if (error) {
    return (
      <div className="text-red-600">Error loading access requests: {String(error)}</div>
    );
  }

  return (
    <div className={styles.InvitesTable}>
      <div className="flex flex-row items-center justify-between mb-4">
        <h2>Access Requests</h2>
      </div>

      {isLoading ? (
        <SkeletonTable columns={4} rows={3} headers={["Name", "Email", "Requested At", "Actions"]} />
      ) : (
        <div className="table-container">
          <div className="table-scroll-wrapper">
            <table
              className="w-full border-collapse border border-gray-300"
              aria-label="Access requests table"
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
                      className="border border-gray-300 px-4 py-8 text-center text-gray-500"
                    >
                      No pending access requests
                    </td>
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
        </div>
      )}
    </div>
  );
};
