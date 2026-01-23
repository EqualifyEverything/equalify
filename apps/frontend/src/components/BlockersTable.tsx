import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  ColumnDef,
  RowExpanding,
  RowData,
} from "@tanstack/react-table";
import * as API from "aws-amplify/api";
import { useState, useMemo, ChangeEvent } from "react";
//import { formatDate } from "../utils";
import * as ToggleGroup from "@radix-ui/react-toggle-group";
import { AccessibleIcon } from "@radix-ui/react-accessible-icon";
import Select, { MultiValue } from "react-select";
import {
  FaArrowDown,
  FaArrowUp,
  FaClipboard,
  FaCode,
  FaRegFilePdf,
} from "react-icons/fa";
import { PiFileHtml } from "react-icons/pi";
import { AiFillFileUnknown, AiOutlineFileUnknown } from "react-icons/ai";
import { Drawer } from "vaul-base";
import * as Tooltip from "@radix-ui/react-tooltip";
//import * as Switch from "@radix-ui/react-switch";
import { useDebounce, useGlobalStore } from "../utils";
//import { MdOutlineCancel } from "react-icons/md";
import themeVariables from "../global-styles/variables.module.scss";
import { PrismLight as SyntaxHighlighter } from "react-syntax-highlighter";
import jsx from "react-syntax-highlighter/dist/esm/languages/prism/jsx";
import { a11yDark as prism } from "react-syntax-highlighter/dist/esm/styles/prism";
import { StyledButton } from "./StyledButton";
import { TbEye, TbEyeX } from "react-icons/tb";
import style from "./BlockersTable.module.scss";
import { SkeletonBlockersTable } from "./Skeleton";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { useDebouncedCallback } from 'use-debounce';
import { Link } from "react-router-dom";

SyntaxHighlighter.registerLanguage("jsx", jsx);

const apiClient = API.generateClient();

interface BlockerTag {
  id: string;
  content: string;
}

interface Blocker {
  id: string;
  short_id: string;
  created_at: string;
  url: string;
  url_id: string;
  content: string;
  ignored: boolean;
  //equalified: boolean;
  messages: string[];
  tags: BlockerTag[];
  categories: string[];
  type: string;
}

interface BlockersTableProps {
  auditId: string;
  isShared: boolean;
}

interface Option {
  value: string;
  label: string;
}

declare module '@tanstack/table-core' {
  interface ColumnMeta<TData extends RowData, TValue> {
    className?: string; // Add your custom property
  }
}

export const BlockersTable = ({ auditId, isShared }: BlockersTableProps) => {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);

  const [selectedTags, setSelectedTags] = useState<Option[]>([]);
  const [availableTags, setAvailableTags] = useState<Option[]>([]); // Added to prevent content flicker while fetching

  const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);
  const [availableCategories, setAvailableCategories] = useState<Option[]>([]); // Added to prevent content flicker while fetching

  const [selectedStatus, setSelectedStatus] = useState<string>("active");

  const [selectedContentType, setSelectedContentType] = useState<string>("all");

  const [searchString, setSearchString] = useState<string>("");

  const [sortBy, setSortBy] = useState<string>("created_at");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");

  const { setAnnounceMessage, authenticated } = useGlobalStore();

  // Query to get ignored blockers for this audit
  const { data: ignoredBlockers } = useQuery({
    queryKey: ["ignoredBlockers", auditId],
    queryFn: async () => {
      const response = await apiClient.graphql({
        query: `query ($audit_id: uuid!) {
          ignored_blockers(where: {audit_id: {_eq: $audit_id}}) {
            blocker_id
          }
        }`,
        variables: { audit_id: auditId },
      });
      const data = response as any;
      return new Set(
        data.data.ignored_blockers.map((ib: any) => ib.blocker_id)
      );
    },
  });

  // Mutation to toggle ignore status
  const toggleIgnoreMutation = useMutation({
    mutationFn: async ({
      blockerId,
      isCurrentlyIgnored,
    }: {
      blockerId: string;
      isCurrentlyIgnored: boolean;
    }) => {
      if (isCurrentlyIgnored) {
        // Delete from ignored_blockers
        await apiClient.graphql({
          query: `mutation ($audit_id: uuid!, $blocker_id: uuid!) {
            delete_ignored_blockers(where: {
              audit_id: {_eq: $audit_id},
              blocker_id: {_eq: $blocker_id}
            }) {
              affected_rows
            }
          }`,
          variables: { audit_id: auditId, blocker_id: blockerId },
        });
      } else {
        // Insert into ignored_blockers
        await apiClient.graphql({
          query: `mutation ($audit_id: uuid!, $blocker_id: uuid!) {
            insert_ignored_blockers_one(object: {
              audit_id: $audit_id,
              blocker_id: $blocker_id
            }) {
              audit_id
              blocker_id
            }
          }`,
          variables: { audit_id: auditId, blocker_id: blockerId },
        });
      }
    },
    onSuccess: () => {
      // Refetch the ignored blockers list
      queryClient.invalidateQueries({ queryKey: ["ignoredBlockers", auditId] });
    },
  });

  const { data, isLoading, error } = useQuery({
    queryKey: [
      "auditBlockers",
      auditId,
      page,
      pageSize,
      selectedTags,
      selectedCategories,
      selectedStatus,
      selectedContentType,
      sortBy,
      sortOrder,
      searchString
    ],
    queryFn: async () => {
      console.log(searchString);
      const params: Record<string, string> = {
        id: auditId,
        page: page.toString(),
        pageSize: pageSize.toString(),
        contentType: selectedContentType,
        sortBy: sortBy,
        sortOrder: sortOrder,
        //searchString: searchString
      };
      if (selectedTags.length > 0) {
        //params.tags = selectedTags.join(',');
        params.tags = selectedTags.map((tag) => tag.value).join(",");
      }
      if (selectedCategories.length > 0) {
        params.categories = selectedCategories
          .map((tag) => tag.value)
          .join(",");
      }
      if (selectedStatus) {
        params.status = selectedStatus;
      }

      if (searchString.length >= 3 || searchString == "") {
        params.searchString = searchString
      }

      console.log("Blockers table refresh...", params);
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getAuditTable",
        options: { queryParams: params },
      }).response;
      const resp = (await response.body.json()) as any;

      // we need to parse the server data to convert BlockerTag[] to Options[]
      resp.availableTags = resp.availableTags?.map((tag: BlockerTag) => ({
        value: tag.id,
        label: tag.content,
      }));
      // Then we store it in local state
      setAvailableTags(resp.availableTags);

      resp.availableCategories = resp.availableCategories?.map(
        (category: string) => ({ value: category, label: category })
      );
      setAvailableCategories(resp.availableCategories);

      return resp;
    },
    refetchInterval: Infinity,
    placeholderData: (previousData) => previousData,
  });

  const getElementTagFromContent = (content: string) => {
    const parser = new DOMParser();
    const extractedElementTag = `<${parser.parseFromString(content, "text/html").body?.firstChild?.nodeName.toLowerCase()}>`;
    return extractedElementTag !== "<undefined>"
      ? extractedElementTag
      : undefined;
  };

  const copyToClipboard = async (val: string) => {
    try {
      await navigator.clipboard.writeText(val);
      console.log(`"${val}" copied to clipboard!`);
      setAnnounceMessage(`"${val}" copied to clipboard!`, "success");
    } catch (err) {
      console.error("Failed to copy: ", err);
    }
  };

  const TAGS_TO_SHOW_IN_TABLE = 1;

  const columns = useMemo<ColumnDef<Blocker>[]>(
    () => [
      {
        accessorKey: "type",
        header: "Type",
        cell: ({ getValue }) => {
          const theType = getValue() as string;
          if (theType?.toLowerCase() === "html") {
            return (
              <AccessibleIcon label="HTML">
                <PiFileHtml className="icon-small" />
              </AccessibleIcon>
            );
          } else if (theType?.toLowerCase() === "pdf") {
            return (
              <AccessibleIcon label="PDF">
                <FaRegFilePdf className="icon-small" />
              </AccessibleIcon>
            );
          } else {
            return (
              <AccessibleIcon label="File Type Unknown">
                <AiOutlineFileUnknown className="icon-small" />
              </AccessibleIcon>
            );
          }
        },
      },
      /*  {
        accessorKey: "short_id",
        header: "ID",
        cell: ({ getValue }) => {
          const shortId = getValue() as string;
          return (
            <code className="text-sm font-bold bg-gray-100 px-2 py-1 rounded">
              {shortId || "N/A"}
            </code>
          );
        },
      },
 */
      {
        accessorKey: "url",
        meta: {
          className: style["url"],
        },
        header: () => (
          <StyledButton
            onClick={handleSortByUrl}
            className="font-small"
            label={`Sort by URL ${sortBy === "url" ? (sortOrder === "asc" ? "descending" : "ascending") : ""}`}
            icon={sortOrder === "asc" ? <FaArrowUp /> : <FaArrowDown />}
            variant="naked"
            showLabel={false}
            prependText="URL"
          >
            {/* URL
            {sortBy === "url" && (
              <span className="text-xs" aria-label={`Sorted ${sortOrder}`}>
                {sortOrder === "asc" ? "▲" : "▼"}
              </span>
            )} */}
          </StyledButton>
        ),
        cell: ({ getValue }) => {
          const url = getValue() as string;
          return (
            <a
              href={url}
              target="_blank"
              rel="noopener noreferrer"
              className="text-blue-600 hover:underline break-all block max-w-xs"
            >
              {url}
            </a>
          );
        },
      },
      {
        accessorKey: "messages",
        header: "Issue",
        meta: {
          className: style["issue"],
        },
        cell: ({ getValue, row }) => {
          const messages = getValue() as string[];
          const shortId = row.original.short_id;
          return (
            <>
              <div className="text-sm max-w-sm">
                {messages[0] || "No message"}
              </div>
              <div style={{ display: "inline-flex" }}>
                <Link to={"/blockers/" + shortId}>{shortId}</Link>
                <StyledButton
                  onClick={() => copyToClipboard(shortId)}
                  icon={<FaClipboard className="icon-small" />}
                  label={shortId || "N/A"}
                  variant={"naked"}
                  showLabel={false}
                />
              </div>
            </>
          );
        },
      },
      {
        accessorKey: "content",
        header: "Code",
        meta: {
          className: style["content"],
        },
        cell: ({ getValue }) => {
          const content = getValue() as string;
          return (
            <>
              {getElementTagFromContent(content) && (
                <div className={style["view-code-details"]}>
                  <span>Issue in element:</span>
                  <code className={style["blocker-code"]}>
                    {getElementTagFromContent(content)}
                  </code>
                </div>
              )}

              <Drawer.Root
                direction="right"
                shouldScaleBackground
                setBackgroundColorOnScale={false}
              >
                <Drawer.Trigger className={style["view-code-button"]}>
                  <FaCode /> <span>View Code</span>
                </Drawer.Trigger>
                <Drawer.Portal>
                  <Drawer.Overlay className="drawer-overlay" />
                  <Drawer.Content className="drawer-content">
                    {/* <Drawer.Handle className="top-4" />
                     */}
                    <div className="drawer-content-inner">
                      <h4>Blocker Code</h4>
                      <SyntaxHighlighter
                        style={prism}
                        language={"jsx"}
                        className="drawer-code"
                      /* wrapLines={true}
                      wrapLongLines={true} */
                      >
                        {content}
                      </SyntaxHighlighter>
                      <Drawer.Close className={"drawer-content-close"}>
                        Close
                      </Drawer.Close>
                    </div>
                  </Drawer.Content>
                </Drawer.Portal>
              </Drawer.Root>
            </>
          );
        },
      },
      {
        accessorKey: "tags",
        header: "Tags",
        meta: {
          className: style["tags"],
        },
        cell: ({ getValue }) => {
          const tags = getValue() as BlockerTag[];
          return (
            <div className="tags">
              {tags.slice(0, TAGS_TO_SHOW_IN_TABLE).map((tag) => (
                <span key={tag.id} className="tag">
                  {tag.content}
                </span>
              ))}
              {tags.slice(TAGS_TO_SHOW_IN_TABLE).length > 0 && (
                <Tooltip.Provider>
                  <Tooltip.Root>
                    <Tooltip.Trigger className={style["tooltip-rondel"]}>
                      {`+${tags.slice(TAGS_TO_SHOW_IN_TABLE).length}`}
                    </Tooltip.Trigger>
                    <Tooltip.Portal>
                      <Tooltip.Content
                        side="bottom"
                        className="tooltip"
                        collisionPadding={8}
                      >
                        <div className="tags">
                          {tags.slice(TAGS_TO_SHOW_IN_TABLE).map((tag) => (
                            <span key={tag.id} className="tag">
                              {tag.content}
                            </span>
                          ))}
                        </div>
                        <Tooltip.Arrow className="tooltip-arrow" />
                      </Tooltip.Content>
                    </Tooltip.Portal>
                  </Tooltip.Root>
                </Tooltip.Provider>
              )}
            </div>
          );
        },
      },
      {
        accessorKey: "categories",
        header: "Category",
        meta: {
          className: style["categories"],
        },
        cell: ({ getValue }) => {
          const category = getValue() as string;
          return (
            <div className="category tags">
              <span className="tag">{category}</span>
            </div>
          );
        },
      },
      /* 
      {
        accessorKey: "ignored",
        header: "Status",
        cell: ({ getValue }) => {
          const ignored = getValue() as boolean;
          return (
            <span
              className={`px-2 py-1 rounded text-xs ${
                ignored
                  ? "bg-gray-200 text-gray-800"
                  : "bg-green-200 text-green-800"
              }`}
            >
              {ignored ? "Ignored" : "Active"}
            </span>
          );
        },
      }, */ /* {
        accessorKey: "id",
        header: "Ignore",

        cell: ({ getValue }) => {
          const blockerId = getValue() as string;
          const isIgnored = ignoredBlockers?.has(blockerId) || false;
          return (
            <input
              type="checkbox"
              checked={isIgnored}
              onChange={() => {
                toggleIgnoreMutation.mutate({
                  blockerId,
                  isCurrentlyIgnored: isIgnored,
                });
                setAnnounceMessage(
                  `Blocker ID ${blockerId} set to ignored status: ${isIgnored}`
                );
              }}
              aria-label={`Ignore blocker ${blockerId}`}
              className="w-4 h-4 cursor-pointer"
            />
          );
        },
      }, */
      {
        accessorKey: "id",
        header: "Ignore",
        cell: ({ getValue }) => {
          const blockerId = getValue() as string;
          const isIgnored = ignoredBlockers?.has(blockerId) || false;
          return (
            <StyledButton
              onClick={() => {
                if (!authenticated) return;
                toggleIgnoreMutation.mutate({
                  blockerId,
                  isCurrentlyIgnored: isIgnored,
                });
                setAnnounceMessage(
                  `Blocker ID ${blockerId} set to ignored status: ${isIgnored ? "Ignored" : "Active"}`,
                  "success"
                );
              }}
              label={isIgnored ? "Ignored" : "Active"}
              icon={
                isIgnored ? (
                  <TbEyeX className="icon-small" />
                ) : (
                  <TbEye className="icon-small" />
                )
              }
              variant={isIgnored ? "toggle-ignored" : "toggle"}
            />
          );
        },
      },
      /* {
            accessorKey: 'created_at',
            header: 'Date',
            cell: ({ getValue }) => {
                const date = getValue() as string;
                return <span className='text-sm whitespace-nowrap'>{formatDate(date)}</span>;
            },
        }, */
    ],
    [sortBy, sortOrder, ignoredBlockers, toggleIgnoreMutation]
  );

  const table = useReactTable({
    data: data?.blockers || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: data?.pagination?.totalPages || 0,
  });

  if (error) {
    return (
      <div className="text-red-600">
        Error loading blockers: {String(error)}
      </div>
    );
  }

  const hasFilters =
    selectedTags.length > 0 || selectedCategories.length > 0 || selectedStatus;
  const filterCount =
    selectedTags.length + selectedCategories.length + (selectedStatus ? 1 : 0);

  const handleTagToggle = (selected: MultiValue<Option>) => {
    setSelectedTags(selected as Option[]);
    setPage(0);
  };

  const handleCategoryToggle = (selected: MultiValue<Option>) => {
    setSelectedCategories(selected as Option[]);
    setPage(0);
  };

  const handleStatusChange = (status: string) => {
    setSelectedStatus(status);
    setPage(0);
  };

  const handleContentTypeChange = (contentType: string) => {
    setSelectedContentType(contentType);
    setPage(0);
  };

  const handlePageSizeChange = (e: ChangeEvent<HTMLSelectElement>) => {
    setPageSize(parseInt(e.target.value));
  };

  const handleSortByUrl = () => {
    if (sortBy === "url") {
      // Toggle sort order if already sorting by URL
      setSortOrder(sortOrder === "asc" ? "desc" : "asc");
      setAnnounceMessage(`Sorting by URL ${sortOrder}`);
    } else {
      // Start sorting by URL in ascending order
      setSortBy("url");
      setSortOrder("asc");
    }
    setPage(0);
  };

  const handleSearch = useDebouncedCallback(
    // function
    (value) => {
      //if(value.length >= 3)
      setSearchString(value);
    },
    750
  );

  const clearAllFilters = () => {
    setSelectedTags([]);
    setSelectedCategories([]);
    setSelectedStatus("all");
    setPage(0);
  };

  return (
    <div className={style.BlockersTable}>
      <div>
        <h2>
          Blockers
          {/* {hasFilters &&
            ` (${filterCount} filter${filterCount !== 1 ? "s" : ""} active)`} */}
        </h2>
      </div>

      {/* Filter Controls */}
      <div>
        <div className="filter-group">
          {/* Status Filter */}
          <div className="status-toggle-group">
            <label htmlFor="statusToggleGroup" className="sr-only">
              Status:
            </label>
            <div>
              <ToggleGroup.Root
                id="statusToggleGroup"
                type="single"
                defaultValue="all"
                aria-label="View by status:"
                value={selectedStatus}
                onValueChange={handleStatusChange}
              >
                <ToggleGroup.Item value="active" aria-label="Active">
                  Active{" "}
                  {data?.statusCounts?.active !== undefined &&
                    `(${data.statusCounts.active})`}
                </ToggleGroup.Item>
                <ToggleGroup.Item value="ignored" aria-label="Ignored">
                  Ignored{" "}
                  {data?.statusCounts?.ignored !== undefined &&
                    `(${data.statusCounts.ignored})`}
                </ToggleGroup.Item>
                <ToggleGroup.Item value="all" aria-label="All">
                  All{" "}
                  {data?.statusCounts?.all !== undefined &&
                    `(${data.statusCounts.all})`}
                </ToggleGroup.Item>
              </ToggleGroup.Root>
            </div>
          </div>

          {/* Content Type Filter */}
          <div className="content-toggle-group">
            <label htmlFor="contentToggleGroup" className="sr-only">
              Content Type:
            </label>
            <div>
              <ToggleGroup.Root
                className="root"
                id="contentToggleGroup"
                type="single"
                defaultValue="all"
                aria-label="View by status:"
                value={selectedContentType}
                onValueChange={handleContentTypeChange}
              >
                <ToggleGroup.Item value="all" aria-label="All">
                  All
                </ToggleGroup.Item>
                <ToggleGroup.Item value="html" aria-label="HTML">
                  HTML
                </ToggleGroup.Item>
                <ToggleGroup.Item value="pdf" aria-label="PDF">
                  PDF
                </ToggleGroup.Item>
              </ToggleGroup.Root>
            </div>
          </div>

          {/* Tag Filter */}
          {availableTags && availableTags.length > 0 && (
            <Select
              className="react-select tag-select"
              options={availableTags}
              isMulti
              value={selectedTags}
              placeholder="Filter by Tags..."
              aria-label="Filter by Tags"
              onChange={handleTagToggle}
              styles={{
                control: (baseStyles, state) => ({
                  ...baseStyles,
                  borderRadius: themeVariables.spacing,
                  fontSize: "13px",
                  minHeight: "24px",
                }),
                dropdownIndicator: (baseStyles, state) => ({
                  ...baseStyles,
                  padding: "4px",
                }),
                clearIndicator: (baseStyles, state) => ({
                  ...baseStyles,
                  padding: "4px",
                }),
              }}
            />
          )}

          {/* Type Filter */}
          {availableCategories && availableCategories.length > 0 && (
            <Select
              className="react-select categories-select"
              options={availableCategories}
              isMulti
              value={selectedCategories}
              placeholder="Filter by Categories..."
              aria-label="Filter by Categories"
              onChange={handleCategoryToggle}
              styles={{
                control: (baseStyles, state) => ({
                  ...baseStyles,
                  borderRadius: themeVariables.spacing,
                  fontSize: "13px",
                  minHeight: "24px",
                }),
                dropdownIndicator: (baseStyles, state) => ({
                  ...baseStyles,
                  padding: "4px",
                }),
                clearIndicator: (baseStyles, state) => ({
                  ...baseStyles,
                  padding: "4px",
                }),
              }}
            />
          )}

          {/* Clear Filters Button */}
          {/* {hasFilters && (
            <div>
              <button onClick={clearAllFilters} className="clear-button">
                <AccessibleIcon label="Clear All Filters">
                  <MdOutlineCancel className="icon-small" />
                </AccessibleIcon>
              </button>
            </div>
          )} */}
          {/* Search Filter */}
          <div>
            <StyledLabeledInput className={style["search-input"]}>
              <label>Search by URL</label>
              <input onChange={(e) => handleSearch(e.target.value)} />
            </StyledLabeledInput>
          </div>
        </div>
      </div>

      {isLoading ? (
        <SkeletonBlockersTable rows={8} />
      ) : (
        <>
          <div className="table-container">
            <table aria-label="Blockers table">
              <thead>
                {table.getHeaderGroups().map((headerGroup) => (
                  <tr key={headerGroup.id}>
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
                    <td colSpan={columns.length}>No blockers found</td>
                  </tr>
                ) : (
                  table.getRowModel().rows.map((row, index) => (
                    <tr key={row.id}>
                      {row.getVisibleCells().map((cell) => (
                        <td key={cell.id} className={cell.column.columnDef.meta?.className ?? ""}>
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
                Showing {data?.blockers?.length || 0} of{" "}
                {data?.pagination?.totalCount || 0} blockers
                {data?.pagination &&
                  ` (Page ${page + 1} of ${data.pagination.totalPages})`}
              </div>
              <div className="pagination-buttons">
                <label htmlFor="pageSize">Blockers per page:</label>
                <select
                  id="pageSize"
                  value={pageSize}
                  onChange={handlePageSizeChange}
                >
                  <option value="10">10</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <button
                  onClick={() => setPage(0)}
                  disabled={page === 0}
                  aria-label="Go to first page"
                >
                  First
                </button>
                <button
                  onClick={() => setPage((p) => Math.max(0, p - 1))}
                  disabled={page === 0}
                  aria-label="Go to previous page"
                >
                  Previous
                </button>
                <button
                  onClick={() => setPage((p) => p + 1)}
                  disabled={
                    !data?.pagination || page >= data.pagination.totalPages - 1
                  }
                  aria-label="Go to next page"
                >
                  Next
                </button>
                <button
                  onClick={() =>
                    data?.pagination && setPage(data.pagination.totalPages - 1)
                  }
                  disabled={
                    !data?.pagination || page >= data.pagination.totalPages - 1
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
