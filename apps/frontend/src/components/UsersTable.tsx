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
import { StyledButton } from "./StyledButton";
import styles from "./UsersTable.module.scss";


const apiClient = API.generateClient();

interface User {
  id: string;
  email: string;
  name: string;
  type: string;
  created_at: string;
}

export const UsersTable = () => {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(50);
  const { setAnnounceMessage } = useGlobalStore();

  // Query to get users
  const { data, isLoading, error } = useQuery({
    queryKey: ["users", page, pageSize],
    queryFn: async () => {
      const response = await apiClient.graphql({
        query: `query($limit: Int!, $offset: Int!) {
          users(
            limit: $limit, 
            offset: $offset, 
            order_by: {created_at: desc}
          ) {
            id
            email
            name
            type
            created_at
          }
          users_aggregate {
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
        users: data.data.users,
        totalCount: data.data.users_aggregate.aggregate.count,
        totalPages: Math.ceil(
          data.data.users_aggregate.aggregate.count / pageSize
        ),
      };
    },
  });

  // Mutation to update user type
  const updateTypeMutation = useMutation({
    mutationFn: async ({ userId, type }: { userId: string; type: string }) => {
      await apiClient.graphql({
        query: `mutation($id: uuid!, $type: String!) {
          update_users_by_pk(pk_columns: {id: $id}, _set: {type: $type}) {
            id
            type
          }
        }`,
        variables: { id: userId, type },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      setAnnounceMessage("User type updated successfully!", "success");
    },
    onError: (error) => {
      console.error("Failed to update type:", error);
      setAnnounceMessage("Failed to update user type", "error");
    },
  });

  // Mutation to delete user
  const deleteUserMutation = useMutation({
    mutationFn: async (userId: string) => {
      await apiClient.graphql({
        query: `mutation($id: uuid!) {
          delete_users_by_pk(id: $id) {
            id
          }
        }`,
        variables: { id: userId },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      setAnnounceMessage("User removed.", "success");
    },
  });

  const columns = useMemo<ColumnDef<User>[]>(
    () => [
      {
        accessorKey: "name",
        header: "Name",
        cell: ({ getValue }) => {
          const name = getValue() as string;
          return <span className="text-sm">{name || "N/A"}</span>;
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
        accessorKey: "type",
        header: "Type",
        cell: ({ getValue, row }) => {
          const currentType = getValue() as string;
          const userId = row.original.id;
          return (
            <select
              value={currentType || "user"}
              onChange={(e) =>
                updateTypeMutation.mutate({
                  userId,
                  type: e.target.value,
                })
              }
              className="px-2 py-1 border rounded text-sm"
              aria-label={`Change type for ${row.original.email}`}
            >
              <option value="member">Member</option>
              <option value="admin">Admin</option>
            </select>
          );
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
        cell: ({ getValue, row }) => {
          const userId = getValue() as string;
          return (
            <StyledButton
              variant="red"
              label={deleteUserMutation.isPending ? "Removing..." : "Remove"}
              onClick={() => {
                if (
                  confirm(
                    `Are you sure you want to remove ${row.original.email}?`
                  )
                ) {
                  deleteUserMutation.mutate(userId);
                }
              }}
              aria-label={`Remove user ${row.original.email}`}
              disabled={deleteUserMutation.isPending}
            />
          );
        },
      },
    ],
    [updateTypeMutation, deleteUserMutation]
  );

  const table = useReactTable({
    data: data?.users || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: data?.totalPages || 0,
  });

  if (error) {
    return (
      <div className="text-red-600">Error loading users: {String(error)}</div>
    );
  }

  return (
    <div className={styles["UsersTable"]}>
      <div>
        <h2>Users</h2>
      </div>

      {isLoading ? (
        <SkeletonTable columns={5} rows={5} headers={["Name", "Email", "Type", "Created At", "Actions"]} />
      ) : (
        <>
          <div className="table-container">
            <table
              className="w-full border-collapse border border-gray-300"
              aria-label="Users table"
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
                      No users found
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
              <div className="pagination-text">
                Showing {data?.users?.length || 0} of {data?.totalCount || 0}{" "}
                users
                {data && ` (Page ${page + 1} of ${data.totalPages})`}
              </div>
              <div className="pagination-buttons">
                <StyledButton
                  label="First"
                  onClick={() => setPage(0)}
                  disabled={page === 0}
                  aria-label="Go to first page"
                />
                <StyledButton
                  label="Previous"
                  onClick={() => setPage((p) => Math.max(0, p - 1))}
                  disabled={page === 0}
                  aria-label="Go to previous page"
                />
                <StyledButton
                  label="Next"
                  onClick={() => setPage((p) => p + 1)}
                  disabled={!data || page >= data.totalPages - 1}
                  aria-label="Go to next page"
                />
                <StyledButton
                  label="Last"
                  onClick={() => data && setPage(data.totalPages - 1)}
                  disabled={!data || page >= data.totalPages - 1}
                  aria-label="Go to last page"
                />
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
};
