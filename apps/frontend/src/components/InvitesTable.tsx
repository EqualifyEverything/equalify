import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  ColumnDef,
} from "@tanstack/react-table";
import * as API from "aws-amplify/api";
import { useState, useMemo } from "react";
import { useGlobalStore } from "../utils";
import { SkeletonTable } from "./Skeleton";

const apiClient = API.generateClient();

interface Invite {
  id: string;
  email: string;
  created_at: string;
}

export const InvitesTable = () => {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(50);
  const [newEmail, setNewEmail] = useState("");
  const { setAnnounceMessage } = useGlobalStore();

  // Query to get invites
  const { data, isLoading, error } = useQuery({
    queryKey: ["invites", page, pageSize],
    queryFn: async () => {
      const response = await apiClient.graphql({
        query: `query($limit: Int!, $offset: Int!) {
          invites(
            limit: $limit, 
            offset: $offset, 
            order_by: {created_at: desc}
          ) {
            id
            email
            created_at
          }
          invites_aggregate {
            aggregate {
              count
            }
          }
        }`,
        variables: {
          limit: pageSize,
          offset: page * pageSize,
        },
      });
      const data = response as any;
      return {
        invites: data.data.invites,
        totalCount: data.data.invites_aggregate.aggregate.count,
        totalPages: Math.ceil(
          data.data.invites_aggregate.aggregate.count / pageSize
        ),
      };
    },
  });

  // Mutation to create invite
  const createInviteMutation = useMutation({
    mutationFn: async (email: string) => {
      // Call the backend API to send invite
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/inviteUser",
          options: {
            body: { email },
          },
        }).response
      ).body.json();
      if (response?.status === "error") {
        console.error("Failed to create invite:", response?.message);
        window.alert(`Failed to send invite: ${response?.message}`);
        setAnnounceMessage(`Failed to send invite: ${response?.message}`, "error");
      } else if (response?.status === "success") {
        queryClient.invalidateQueries({ queryKey: ["invites"] });
        setNewEmail("");
        setAnnounceMessage("Invite sent successfully!", "success");
      }
    },
  });

  // Mutation to delete invite
  const deleteInviteMutation = useMutation({
    mutationFn: async (inviteId: string) => {
      await apiClient.graphql({
        query: `mutation($id: uuid!) {
          delete_invites_by_pk(id: $id) {
            id
          }
        }`,
        variables: { id: inviteId },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["invites"] });
      setAnnounceMessage("Invite deleted", "success");
    },
  });

  const columns = useMemo<ColumnDef<Invite>[]>(
    () => [
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
        header: "Created At",
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
        cell: ({ getValue }) => {
          const inviteId = getValue() as string;
          return (
            <button
              onClick={() => deleteInviteMutation.mutate(inviteId)}
              className="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm disabled:opacity-50"
              aria-label={`Delete invite`}
              disabled={deleteInviteMutation.isPending}
            >
              {deleteInviteMutation.isPending ? "Deleting..." : "Delete"}
            </button>
          );
        },
      },
    ],
    [deleteInviteMutation]
  );

  const table = useReactTable({
    data: data?.invites || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: data?.totalPages || 0,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newEmail.trim()) {
      createInviteMutation.mutate(newEmail.trim());
    }
  };

  if (error) {
    return (
      <div className="text-red-600">Error loading invites: {String(error)}</div>
    );
  }

  return (
    <div className="mt-8">
      <div className="flex flex-row items-center justify-between mb-4">
        <h2>Invites</h2>
        <form onSubmit={handleSubmit} className="flex gap-2">
          <input
            type="email"
            value={newEmail}
            onChange={(e) => setNewEmail(e.target.value)}
            placeholder="Email address"
            className="px-3 py-2 border rounded"
            required
            aria-label="Email address for invite"
          />
          <button
            type="submit"
            className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            disabled={createInviteMutation.isPending}
          >
            {createInviteMutation.isPending ? "Sending..." : "Invite"}
          </button>
        </form>
      </div>

      {isLoading ? (
        <SkeletonTable columns={3} rows={3} headers={["Email", "Created At", "Actions"]} />
      ) : (
        <>
          <div className="table-container">
            <table
              className="w-full border-collapse border border-gray-300"
              aria-label="Invites table"
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
                      No invites found
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

            {/* Pagination Controls */}
            <div
              className="pagination"
              role="navigation"
              aria-label="Pagination"
            >
              <div className="text">
                Showing {data?.invites?.length || 0} of {data?.totalCount || 0}{" "}
                invites
                {data && ` (Page ${page + 1} of ${data.totalPages})`}
              </div>
              <div className="pagination-buttons">
                <button
                  onClick={() => setPage(0)}
                  disabled={page === 0}
                  className="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
                  aria-label="Go to first page"
                >
                  First
                </button>
                <button
                  onClick={() => setPage((p) => Math.max(0, p - 1))}
                  disabled={page === 0}
                  className="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
                  aria-label="Go to previous page"
                >
                  Previous
                </button>
                <button
                  onClick={() => setPage((p) => p + 1)}
                  disabled={!data || page >= data.totalPages - 1}
                  className="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
                  aria-label="Go to next page"
                >
                  Next
                </button>
                <button
                  onClick={() => data && setPage(data.totalPages - 1)}
                  disabled={!data || page >= data.totalPages - 1}
                  className="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
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
