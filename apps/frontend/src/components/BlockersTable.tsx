import { useQuery, useMutation } from "@tanstack/react-query";
import {
  useReactTable,
  getCoreRowModel,
  flexRender,
  ColumnDef,
  RowExpanding,
  RowData,
  VisibilityState,
} from "@tanstack/react-table";
import * as API from "aws-amplify/api";
import { useState, useMemo, useEffect, useRef, ChangeEvent, ChangeEventHandler } from "react";
//import { formatDate } from "../utils";
import * as DropdownMenu from "@radix-ui/react-dropdown-menu";
import * as ToggleGroup from "@radix-ui/react-toggle-group";
import * as Collapsible from "@radix-ui/react-collapsible";
import { AccessibleIcon } from "@radix-ui/react-accessible-icon";
import Select, { MultiValue } from "react-select";
import {
  FaAngleDown,
  FaAngleUp,
  FaArrowDown,
  FaArrowUp,
  FaCaretDown,
  FaClipboard,
  FaCode,
  FaDownload,
  FaRegFilePdf,
  FaTimes,
} from "react-icons/fa";
import { PiFileHtml } from "react-icons/pi";
import { AiFillFileUnknown, AiOutlineFileUnknown } from "react-icons/ai";
import { FiExternalLink } from "react-icons/fi";
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
import { BlockerUrlsDrawer } from "./BlockerUrlsDrawer";
import { Card } from "./Card";
import { TbEye, TbEyeX } from "react-icons/tb";
import style from "./BlockersTable.module.scss";
import { useScrollFade } from "#src/utils/useScrollFade.ts";
import { SkeletonBlockersTable } from "./Skeleton";
import { StyledLabeledInput } from "./StyledLabeledInput";
import {
  getAccessibilityStandardLabel,
  getAccessibilityStandardTagInfo,
  ACCESSIBILITY_STANDARD_GROUP_ORDER,
} from "#src/utils/accessibilityStandardTags.ts";
import { useDebouncedCallback } from 'use-debounce';
import { Link, useSearchParams } from "react-router-dom";
import { BlockersTableColumnToggle } from "./BlockersTableColumnToggle";
import { useIgnoredBlockers, useToggleIgnore } from "../hooks";

SyntaxHighlighter.registerLanguage("jsx", jsx);


const triggerCsvDownload = (csv: string, filename: string) => {
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", filename);
  link.style.visibility = "hidden";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

interface BlockerTag {
  id: string;
  content: string;
}

export interface Blocker {
  id: string;
  short_id: string;
  content_hash_id: string;
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
  duplicateCount: number;
}

interface BlockersTableProps {
  auditId: string;
  isShared: boolean;
}

interface Option {
  value: string;
  label: string;
}

interface GroupedOption {
  label: string;
  options: Option[];
}

declare module '@tanstack/table-core' {
  interface ColumnMeta<TData extends RowData, TValue> {
    className?: string; // Add your custom property
  }
}

export const BlockersTable = ({ auditId, isShared }: BlockersTableProps) => {
  const [scrollFadeRef, showScrollFade] = useScrollFade();
  const [searchParams, setSearchParams] = useSearchParams();
  const page = parseInt(searchParams.get("page") ?? "0", 10);
  const pageSize = parseInt(searchParams.get("pageSize") ?? "10", 10);

  const setPage = (updater: number | ((p: number) => number)) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      const newPage = typeof updater === "function" ? updater(page) : updater;
      if (newPage === 0) next.delete("page");
      else next.set("page", newPage.toString());
      return next;
    });
  };

  const [selectedTags, setSelectedTags] = useState<Option[]>([]);
  const [availableTags, setAvailableTags] = useState<GroupedOption[]>([]); // Added to prevent content flicker while fetching

  const [selectedCategories, setSelectedCategories] = useState<Option[]>(() => {
    const categories = searchParams.get("categories");
    return categories
      ? categories.split(",").filter(Boolean).map((category) => ({ value: category, label: category }))
      : [];
  });
  const [availableCategories, setAvailableCategories] = useState<Option[]>([]); // Added to prevent content flicker while fetching

  const [selectedStatus, setSelectedStatus] = useState<string>("active");

  const [selectedContentType, setSelectedContentType] = useState<string>("all");

  const [searchString, setSearchString] = useState<string>(() => searchParams.get("search") ?? "");
  const searchInputRef = useRef<HTMLInputElement>(null);
  // Tracks whether the (uncontrolled) search input currently has text, purely
  // to show/hide the clear button — updated on every keystroke, unlike
  // searchString itself which only catches up once the debounce settles.
  const [searchHasText, setSearchHasText] = useState<boolean>(() => searchString.length > 0);

  const [sortBy, setSortBy] = useState<string>("created_at");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");

  const {
    setAnnounceMessage,
    authenticated,
    blockerTableColumnVisibility,
    setBlockerTableColumnVisibility,
    darkMode,
    blockerTableAdvancedFiltersOpen,
    setBlockerTableAdvancedFiltersOpen,
  } = useGlobalStore();

  // Matches the resting (unfocused) look of the plain <select> filters
  // (global-styles/inputs.scss + selects.scss for light mode, dark.scss's
  // `body.dark select` for dark mode) so the two react-select multiboxes
  // read as the same kind of control, not a visually distinct widget.
  // Focus states are deliberately left as react-select's own
  // border/box-shadow (`base.borderColor`/`base.boxShadow`) rather than
  // reimplemented here — overriding those for every state would risk
  // silently weakening the focus indicator keyboard users rely on.
  const selectBg = darkMode ? themeVariables.dark_surface : themeVariables.white;
  const selectBorder = darkMode ? themeVariables.dark_border : themeVariables.gray;
  const selectText = darkMode ? themeVariables.paper : themeVariables.black;
  const selectStyles = {
    control: (base: any, state: any) => ({
      ...base,
      backgroundColor: selectBg,
      borderColor: state.isFocused ? base.borderColor : selectBorder,
      borderRadius: `calc(${themeVariables.spacing} / 2)`,
      boxShadow: state.isFocused ? base.boxShadow : themeVariables["shadow-inset"],
      color: selectText,
      fontSize: "16px",
      cursor: "pointer",
    }),
    menu: (base: any) => ({
      ...base,
      backgroundColor: selectBg,
      borderColor: selectBorder,
    }),
    option: (base: any, state: any) => ({
      ...base,
      backgroundColor: state.isSelected
        ? (darkMode ? themeVariables.black : themeVariables.gray)
        : state.isFocused
          ? (darkMode ? themeVariables.dark_border : themeVariables.paper)
          : "transparent",
      color: selectText,
    }),
    multiValue: (base: any) => ({
      ...base,
      backgroundColor: darkMode ? themeVariables.dark_border : themeVariables.paper,
    }),
    multiValueLabel: (base: any) => ({
      ...base,
      color: selectText,
    }),
    multiValueRemove: (base: any) => ({
      ...base,
      color: selectText,
      ":hover": {
        backgroundColor: darkMode ? themeVariables.black : themeVariables.gray,
        color: selectText,
      },
    }),
    placeholder: (base: any) => ({
      ...base,
      color: selectText,
      opacity: 0.5,
    }),
    singleValue: (base: any) => ({
      ...base,
      color: selectText,
    }),
    input: (base: any) => ({
      ...base,
      color: selectText,
    }),
    indicatorSeparator: (base: any) => ({
      ...base,
      backgroundColor: selectBorder,
    }),
    dropdownIndicator: (base: any) => ({
      ...base,
      color: selectText,
      padding: "4px",
      cursor: "pointer",
    }),
    clearIndicator: (base: any) => ({
      ...base,
      color: selectText,
      opacity: 0.6,
      padding: "4px",
    }),
  };

  const { data: ignoredBlockers } = useIgnoredBlockers(auditId);
  const toggleIgnoreMutation = useToggleIgnore(auditId);

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
      // Only tags that name a recognized accessibility standard (WCAG,
      // Section 508, EN 301 549, Trusted Tester) are worth filtering by —
      // axe-core's internal categories and veraPDF's PDF-structure tags
      // share this same "tags" column but aren't meaningful filter values.
      // Grouped by standard and sorted within each group (WCAG/EN 301 549 by
      // criterion number, Section 508 by paragraph letter, Trusted Tester by
      // test number) rather than left in whatever order the backend returned.
      const taggedOptions: { value: string; label: string; group: string; sortKey: string }[] = [];
      for (const tag of (resp.availableTags as BlockerTag[] | undefined) ?? []) {
        const info = getAccessibilityStandardTagInfo(tag.content);
        if (info) taggedOptions.push({ value: tag.id, label: info.label, group: info.group, sortKey: info.sortKey });
      }
      taggedOptions.sort((a, b) => a.sortKey.localeCompare(b.sortKey));

      const optionsByGroup = new Map<string, Option[]>();
      for (const { value, label, group } of taggedOptions) {
        if (!optionsByGroup.has(group)) optionsByGroup.set(group, []);
        optionsByGroup.get(group)!.push({ value, label });
      }
      resp.availableTags = ACCESSIBILITY_STANDARD_GROUP_ORDER
        .filter((group) => optionsByGroup.has(group))
        .map((group) => ({ label: group, options: optionsByGroup.get(group)! }));
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

  // Announce pagination changes to screen readers once the newly requested
  // page has actually loaded (data still holds the previous page until then).
  const announcedPageRef = useRef(page);
  const announcedPageSizeRef = useRef(pageSize);
  useEffect(() => {
    if (!data?.pagination) return;
    if (announcedPageRef.current !== page || announcedPageSizeRef.current !== pageSize) {
      setAnnounceMessage(
        `Showing ${data.blockers?.length ?? 0} of ${data.pagination.totalCount} blockers, page ${page + 1} of ${data.pagination.totalPages}`,
        "normal",
        true
      );
    }
    announcedPageRef.current = page;
    announcedPageSizeRef.current = pageSize;
  }, [data]);

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
      {
        accessorKey: "short_id",
        header: "ID",
        cell: ({ getValue }) => {
          const shortId = getValue() as string;
          const auditIdNoDash = auditId.replace(/-/g, "");
          return (
            <div style={{ display: "inline-flex" }}>
              <Link to={"/shared/" + auditIdNoDash + "/" + shortId}>{shortId}</Link>
              <StyledButton
                onClick={() => copyToClipboard(shortId)}
                icon={<FaClipboard className="icon-small" />}
                label={shortId || "N/A"}
                variant={"naked"}
                showLabel={false}
              />
            </div>
          );
        },
      },
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
            <div className={style["url-cell"]}>
              <a
                href="#"
                onClick={(e) => {
                  e.preventDefault();
                  handleFilterByUrl(url);
                }}
                className={style["url-filter-link"]}
                aria-label={`Filter table to blockers for ${url}`}
              >
                {url}
              </a>
              <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={`Open ${url} in a new tab`}
                className={style["external-link"]}
              >
                <FiExternalLink aria-hidden="true" focusable="false" />
              </a>
            </div>
          );
        },
      },
      {
        accessorKey: "messages",
        header: "Description",
        meta: {
          className: style["issue"],
        },
        cell: ({ getValue, row }) => {
          const messages = getValue() as string[];
          const duplicateCount = row.original.duplicateCount ?? 1;
          return (
            <div className="text-sm max-w-sm">
              {messages[0] || "No message"}
              {duplicateCount > 1 && (
                <div style={{ marginTop: "4px" }}>
                  <BlockerUrlsDrawer
                    auditId={auditId}
                    isShared={isShared}
                    contentHashId={row.original.content_hash_id}
                    occurrences={duplicateCount}
                    triggerLabel={`View all ${duplicateCount} occurrences`}
                  />
                </div>
              )}
            </div>
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
                  <span>Issue element: </span>
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
                  <FaCode /> View Code
                </Drawer.Trigger>
                <Drawer.Portal>
                  <Drawer.Overlay className="drawer-overlay" />
                  <Drawer.Content className="drawer-content">
                    {/* <Drawer.Handle className="top-4" />
                     */}
                    <div className="drawer-content-inner">
                      <div className="drawer-header">
                        <h4>Blocker Code</h4>
                        <Drawer.Close
                          render={(props) => (
                            <StyledButton
                              onClick={props.onClick}
                              label="Close"
                              variant="light"
                              icon={<FaTimes />}
                            />
                          )}
                        />
                      </div>
                      <SyntaxHighlighter
                        style={prism}
                        language={"jsx"}
                        className="drawer-code"
                      /* wrapLines={true}
                      wrapLongLines={true} */
                      >
                        {content}
                      </SyntaxHighlighter>
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
        header: "Accessibility Standards",
        meta: {
          className: style["tags"],
        },
        cell: ({ getValue }) => {
          // Only tags naming a recognized accessibility standard are shown
          // here — axe-core's internal categories and veraPDF's PDF-structure
          // tags share this same column but aren't standards themselves.
          const tags = (getValue() as BlockerTag[])
            .map((tag) => {
              const label = getAccessibilityStandardLabel(tag.content);
              return label ? { id: tag.id, label } : null;
            })
            .filter((tag): tag is { id: string; label: string } => tag !== null);
          return (
            <div className="tags">
              {tags.slice(0, TAGS_TO_SHOW_IN_TABLE).map((tag) => (
                <span key={tag.id} className="tag">
                  {tag.label}
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
                              {tag.label}
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
        header: "Rule",
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
        cell: ({ getValue, row }) => {
          const blockerId = getValue() as string;
          const contentHashId = row.original.content_hash_id;
          const isIgnored = ignoredBlockers?.has(blockerId) || false;
          return (
            <StyledButton
              onClick={() => {
                if (!authenticated) return;
                toggleIgnoreMutation.mutate({
                  blockerId,
                  contentHashId,
                  isCurrentlyIgnored: isIgnored,
                });
                setAnnounceMessage(
                  // isIgnored is the pre-toggle state, so the new status is its opposite
                  `Blocker ID ${blockerId} set to ignored status: ${isIgnored ? "Active" : "Ignored"}`,
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
    [sortBy, sortOrder, ignoredBlockers, toggleIgnoreMutation, auditId, isShared]
  );

  const table = useReactTable({
    data: data?.blockers || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: data?.pagination?.totalPages || 0,
    state: {
      columnVisibility: blockerTableColumnVisibility
    },
    onColumnVisibilityChange: setBlockerTableColumnVisibility
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
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      const size = e.target.value;
      if (size === "10") next.delete("pageSize");
      else next.set("pageSize", size);
      return next;
    });
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

  // Drill down into a single URL's blockers: matches the exact-URL search
  // (quoted, per buildUrlSearchClause on the backend) that
  // BlockersTableSummary's URL links use, but applied in-place since this
  // table is already mounted rather than navigated to.
  const handleFilterByUrl = (url: string) => {
    const quotedUrl = `"${url}"`;
    setSearchString(quotedUrl);
    // The search input is uncontrolled (defaultValue) so its debounce isn't
    // disrupted by re-renders while typing; set it imperatively here since
    // this update doesn't come from typing.
    if (searchInputRef.current) {
      searchInputRef.current.value = quotedUrl;
    }
    setSearchHasText(true);
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev);
      next.set("search", quotedUrl);
      next.delete("page");
      return next;
    });
    setAnnounceMessage(`Filtering blockers to ${url}`);
  };

  const handleSearch = useDebouncedCallback(
    // function
    (value) => {
      //if(value.length >= 3)
      setSearchString(value);
    },
    750
  );

  const handleClearSearch = () => {
    handleSearch.cancel();
    setSearchString("");
    setSearchHasText(false);
    if (searchInputRef.current) {
      searchInputRef.current.value = "";
      searchInputRef.current.focus();
    }
    setPage(0);
    setAnnounceMessage("Search cleared");
  };

  const clearAllFilters = () => {
    setSelectedTags([]);
    setSelectedCategories([]);
    setSelectedStatus("all");
    setPage(0);
  };

  const exportFilteredMutation = useMutation({
    mutationFn: async () => {
      const params: Record<string, string> = {
        id: auditId,
        contentType: selectedContentType,
        sortBy,
        sortOrder,
      };
      if (selectedTags.length > 0) {
        params.tags = selectedTags.map((tag) => tag.value).join(",");
      }
      if (selectedCategories.length > 0) {
        params.categories = selectedCategories.map((tag) => tag.value).join(",");
      }
      if (selectedStatus) {
        params.status = selectedStatus;
      }
      if (searchString.length >= 3 || searchString === "") {
        params.searchString = searchString;
      }
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/exportAuditTable",
        options: { queryParams: params },
      }).response;
      const csv = await response.body.text();
      triggerCsvDownload(csv, `blockers-${auditId}-${new Date().toISOString().split("T")[0]}.csv`);
    },
    onSuccess: () => {
      setAnnounceMessage("Exported filtered blockers to CSV", "success");
    },
    onError: (err) => {
      console.error(err);
      setAnnounceMessage("Failed to export blockers", "error");
    },
  });

  const exportAllMutation = useMutation({
    mutationFn: async () => {
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/exportAuditTable",
        options: { queryParams: { id: auditId } },
      }).response;
      const csv = await response.body.text();
      triggerCsvDownload(csv, `blockers-all-${auditId}-${new Date().toISOString().split("T")[0]}.csv`);
    },
    onSuccess: () => {
      setAnnounceMessage("Exported all blockers to CSV", "success");
    },
    onError: (err) => {
      console.error(err);
      setAnnounceMessage("Failed to export blockers", "error");
    },
  });

  const exportPdfLinksMutation = useMutation({
    mutationFn: async () => {
      const response = await API.get({
        apiName: "auth",
        path: "/exportAuditTablePdfSourceLinks",
        options: { queryParams: { id: auditId } },
      }).response;
      const csv = await response.body.text();
      triggerCsvDownload(csv, `pdf-links-${auditId}-${new Date().toISOString().split("T")[0]}.csv`);
    },
    onSuccess: () => {
      setAnnounceMessage("Exported PDF source links to CSV", "success");
    },
    onError: (err) => {
      console.error(err);
      setAnnounceMessage("Failed to export PDF source links", "error");
    },
  });

  const anyExportPending = exportFilteredMutation.isPending || exportAllMutation.isPending || exportPdfLinksMutation.isPending;

  return (
    <div className={style.BlockersTable}>
      {/* Filter Controls */}
      <div>

        <div className={style["table-top-buttons"]}>
          <div className={style["total-blockers"]} aria-live="polite">
            <span className={style["total-blockers-count"]}>
              {data?.pagination?.totalCount?.toLocaleString() ?? "—"}
            </span>{" "}
            {data?.pagination?.totalCount === 1 ? "Blocker" : "Blockers"}
          </div>
          <div className={style["table-top-actions"]}>
            {/* ColumnToggle */}
            <BlockersTableColumnToggle
              table={table}
            />
            {/* Export CSV Dropdown */}
            <DropdownMenu.Root>
              <DropdownMenu.Trigger
                className={style["export-trigger"]}
                disabled={anyExportPending}
                aria-label="Export CSV options"
              >
                {anyExportPending ? (
                  <span className={style["export-trigger-spinner"]} aria-hidden="true" />
                ) : (
                  <FaDownload aria-hidden="true" />
                )}
                <FaCaretDown className={style["export-trigger-caret"]} aria-hidden="true" />
              </DropdownMenu.Trigger>
              <DropdownMenu.Portal>
                <DropdownMenu.Content
                  className={style["export-dropdown-content"]}
                  align="end"
                  sideOffset={4}
                >
                  <DropdownMenu.Item
                    className={style["export-dropdown-item"]}
                    onSelect={() => exportFilteredMutation.mutate()}
                    disabled={exportFilteredMutation.isPending || !data?.pagination?.totalCount}
                  >
                    <FaDownload className="icon-small" aria-hidden="true" />
                    <div>
                      <div className={style["export-dropdown-item-label"]}>Export filtered blockers</div>
                      <div className={style["export-dropdown-item-desc"]}>
                        {data?.pagination?.totalCount
                          ? `${data.pagination.totalCount.toLocaleString()} blockers matching current filters`
                          : "No blockers match current filters"}
                      </div>
                    </div>
                  </DropdownMenu.Item>
                  <DropdownMenu.Item
                    className={style["export-dropdown-item"]}
                    onSelect={() => exportAllMutation.mutate()}
                    disabled={exportAllMutation.isPending}
                  >
                    <FaDownload className="icon-small" aria-hidden="true" />
                    <div>
                      <div className={style["export-dropdown-item-label"]}>Export all blockers</div>
                      <div className={style["export-dropdown-item-desc"]}>All blockers in this audit, ignoring current filters</div>
                    </div>
                  </DropdownMenu.Item>
                  {!isShared && (
                    <>
                      <DropdownMenu.Separator className={style["export-dropdown-separator"]} />
                      <DropdownMenu.Item
                        className={style["export-dropdown-item"]}
                        onSelect={() => exportPdfLinksMutation.mutate()}
                        disabled={exportPdfLinksMutation.isPending}
                      >
                        <FaRegFilePdf className="icon-small" aria-hidden="true" />
                        <div>
                          <div className={style["export-dropdown-item-label"]}>Export PDF Source Page URLs</div>
                          <div className={style["export-dropdown-item-desc"]}>Source pages and linked PDF URLs found in this audit</div>
                        </div>
                      </DropdownMenu.Item>
                    </>
                  )}
                </DropdownMenu.Content>
              </DropdownMenu.Portal>
            </DropdownMenu.Root>
          </div>
        </div>
        <div className="filter-group">
          {/* Search Filter */}
          <StyledLabeledInput className={style["search-input"]}>
            <label>Search by URL</label>
            <div className={style["search-input-wrapper"]}>
              <input
                ref={searchInputRef}
                defaultValue={searchString}
                onChange={(e) => {
                  setSearchHasText(e.target.value.length > 0);
                  handleSearch(e.target.value);
                }}
              />
              {searchHasText && (
                <StyledButton
                  onClick={handleClearSearch}
                  icon={<FaTimes className="icon-small" />}
                  label="Clear search"
                  variant="naked"
                  showLabel={false}
                  className={style["search-clear-button"]}
                />
              )}
            </div>
          </StyledLabeledInput>

          <div className="filter-group-right">
          {/* Content Type Filter */}
          <StyledLabeledInput>
            <label>Filter by Content Type</label>
            <select
              id="contentToggleGroup"
              defaultValue="all"
              aria-label="Filter by content type:"
              value={selectedContentType}
              onChange={(e: ChangeEvent<HTMLSelectElement>) => handleContentTypeChange(e.target.value)}
            >
              <option value="all">All{" "}
                {data?.typeCounts?.all !== undefined &&
                  `(${data.typeCounts.all})`}</option>
              <option value="html">HTML{" "}
                {data?.typeCounts?.html !== undefined &&
                  `(${data.typeCounts.html})`}</option>
              <option value="pdf">PDF{" "}
                {data?.typeCounts?.pdf !== undefined &&
                  `(${data.typeCounts.pdf})`}</option>
            </select>
          </StyledLabeledInput>

          {/* Tag (Accessibility Standard) Filter */}
          {availableTags && availableTags.length > 0 && (
            <StyledLabeledInput>
              <label>Filter by Accessibility Standard</label>
              <Select
                className="react-select tag-select"
                options={availableTags}
                isMulti
                value={selectedTags}
                placeholder="Accessibility Standard..."
                aria-label="Filter by Accessibility Standard"
                onChange={handleTagToggle}
                styles={selectStyles}
              />
            </StyledLabeledInput>
          )}
          </div>
        </div>

        <Card variant="light" className={style["advanced-filters-card"]}>
          <Collapsible.Root
            open={blockerTableAdvancedFiltersOpen}
            onOpenChange={setBlockerTableAdvancedFiltersOpen}
          >
            <Collapsible.Trigger asChild>
              <StyledButton
                variant="naked"
                label={blockerTableAdvancedFiltersOpen ? "Hide Advanced Filter Options" : "Show Advanced Filter Options"}
                icon={blockerTableAdvancedFiltersOpen ? <FaAngleUp /> : <FaAngleDown />}
                onClick={() => { }}
              />
            </Collapsible.Trigger>
            <Collapsible.Content>
        <div className="filter-group-secondary">
          {/* Status Filter */}
          <StyledLabeledInput>
            <label>Filter by Status</label>
            <select
              id="statusToggleGroup"
              defaultValue="all"
              aria-label="Filter by status:"
              value={selectedStatus}
              onChange={(e: ChangeEvent<HTMLSelectElement>) => handleStatusChange(e.target.value)}
            >
              <option value="active">Active{" "}
                {data?.statusCounts?.active !== undefined &&
                  `(${data.statusCounts.active})`}</option>
              <option value="ignored">Ignored{" "}
                {data?.statusCounts?.ignored !== undefined &&
                  `(${data.statusCounts.ignored})`}</option>
              <option value="all">All{" "}
                {data?.statusCounts?.all !== undefined &&
                  `(${data.statusCounts.all})`}</option>
            </select>
          </StyledLabeledInput>


          {/* Rules Filter */}
          {availableCategories && availableCategories.length > 0 && (
            <StyledLabeledInput>
              <label>Filter by Rules</label>
              <Select
                className="react-select categories-select"
                options={availableCategories}
                isMulti
                value={selectedCategories}
                placeholder="Rules..."
                aria-label="Filter by Rules"
                onChange={handleCategoryToggle}
                styles={selectStyles}
              />
            </StyledLabeledInput>
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


        </div>
            </Collapsible.Content>
          </Collapsible.Root>
        </Card>

      </div>

      {isLoading ? (
        <SkeletonBlockersTable rows={8} />
      ) : (
        <>
          <div className="table-container">
            <div className={"table-scroll-wrapper" + (showScrollFade ? " scroll-fade-active" : "")} ref={scrollFadeRef} tabIndex={0} role="region" aria-label="Blockers table, scrollable horizontally">
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
                    <td colSpan={columns.length} className={style["empty-table"]}>No blockers found</td>
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
            </div>

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
                <div className="page-size-control">
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
                    !data?.pagination || page >= data.pagination.totalPages - 1
                  }
                  label="Next"
                  variant="light"
                />
                <StyledButton
                  onClick={() =>
                    data?.pagination && setPage(data.pagination.totalPages - 1)
                  }
                  disabled={
                    !data?.pagination || page >= data.pagination.totalPages - 1
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
    </div>
  );
};
