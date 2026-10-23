import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { useEffect, useRef, useState } from "react";
import { Drawer } from "vaul-base";
import { FaClipboard, FaTimes } from "react-icons/fa";
import { StyledButton } from "./StyledButton";
import { useGlobalStore } from "../utils";
import style from "./BlockerUrlsDrawer.module.scss";

const URLS_PAGE_SIZE = 25;
const URLS_CSV_FETCH_SIZE = 1000;

interface BlockerUrlsResp {
  urls: string[];
  pagination: {
    page: number;
    pageSize: number;
    totalCount: number;
    totalPages: number;
  };
}

// Which occurrences to list: every copy of a blocker group in one scan,
// optionally leaving one blocker out.
export interface BlockerOccurrencesFilter {
  auditId: string;
  isShared: boolean;
  contentHashId: string;
  // Defaults to the audit's latest scan
  scanId?: string;
  excludeBlockerId?: string;
}

const csvEscape = (value: string) => (/[",\r\n]/.test(value) ? `"${value.replace(/"/g, '""')}"` : value);

const fetchBlockerUrlsPage = async (
  { auditId, isShared, contentHashId, scanId, excludeBlockerId }: BlockerOccurrencesFilter,
  page: number,
  pageSize: number
): Promise<BlockerUrlsResp> => {
  const queryParams: Record<string, string> = {
    id: auditId,
    content_hash_id: contentHashId,
    page: page.toString(),
    pageSize: pageSize.toString(),
  };
  if (scanId) queryParams.scan_id = scanId;
  if (excludeBlockerId) queryParams.exclude_blocker_id = excludeBlockerId;
  const response = await API.get({
    apiName: isShared ? "public" : "auth",
    path: "/getBlockerUrls",
    options: { queryParams },
  }).response;
  return (await response.body.json()) as any as BlockerUrlsResp;
};

// Shared so a caller can check for occurrences (e.g. to decide whether to
// render a table at all) and reuse the table's cached first page.
export const blockerOccurrencesQueryOptions = (
  filter: BlockerOccurrencesFilter,
  page: number = 0,
  pageSize: number = URLS_PAGE_SIZE
) => ({
  queryKey: ["blockerUrls", filter.auditId, filter.contentHashId, filter.scanId ?? null, filter.excludeBlockerId ?? null, pageSize, page],
  queryFn: () => fetchBlockerUrlsPage(filter, page, pageSize),
});

// CSV export needs every URL, not just the page on screen, so this loops the
// same endpoint at its max page size until it has them all.
const fetchAllBlockerUrls = async (filter: BlockerOccurrencesFilter): Promise<string[]> => {
  const all: string[] = [];
  let page = 0;
  while (true) {
    const json = await fetchBlockerUrlsPage(filter, page, URLS_CSV_FETCH_SIZE);
    all.push(...json.urls);
    if (page + 1 >= json.pagination.totalPages) break;
    page += 1;
  }
  return all;
};

export const useCopyBlockerUrlsCsv = (filter: BlockerOccurrencesFilter) => {
  const { setAnnounceMessage } = useGlobalStore();
  const [copying, setCopying] = useState(false);

  const copy = async () => {
    setCopying(true);
    try {
      const urls = await fetchAllBlockerUrls(filter);
      const csv = ["URL", ...urls.map(csvEscape)].join("\r\n");
      await navigator.clipboard.writeText(csv);
      setAnnounceMessage(`Copied ${urls.length} ${urls.length === 1 ? "URL" : "URLs"} as CSV to clipboard!`, "success");
    } catch (err) {
      console.error("Failed to copy URLs as CSV: ", err);
      setAnnounceMessage("Failed to copy URLs as CSV. Please try again.", "error");
    } finally {
      setCopying(false);
    }
  };

  return { copy, copying };
};

// Paginated table of every occurrence of a blocker (one row per occurrence,
// so a URL repeats if the blocker shows up more than once on it). Used by the
// drawer below and by the blocker page's "Also Appears On" section.
export const BlockerOccurrencesTable = ({
  expectedCount,
  enabled = true,
  pageSize = URLS_PAGE_SIZE,
  tableLabel = "Occurrences of this blocker",
  maxHeight,
  ...filter
}: BlockerOccurrencesFilter & {
  // Used for the pagination text until the first page loads
  expectedCount?: number;
  enabled?: boolean;
  pageSize?: number;
  tableLabel?: string;
  maxHeight?: string;
}) => {
  const { setAnnounceMessage } = useGlobalStore();
  const [page, setPage] = useState(0);
  const { data, isLoading } = useQuery({
    ...blockerOccurrencesQueryOptions(filter, page, pageSize),
    enabled,
    placeholderData: (previousData) => previousData,
  });

  const items = data?.urls ?? [];
  const fallbackCount = expectedCount ?? 0;
  const pagination = data?.pagination ?? {
    page: 0,
    pageSize,
    totalCount: fallbackCount,
    totalPages: Math.ceil(fallbackCount / pageSize),
  };

  // Announce pagination changes once the requested page has actually loaded.
  const announcedPageRef = useRef(page);
  useEffect(() => {
    if (!data) return;
    if (announcedPageRef.current !== page) {
      setAnnounceMessage(
        `Showing ${data.urls.length} of ${data.pagination.totalCount} occurrences, page ${page + 1} of ${data.pagination.totalPages}`,
        "normal",
        true
      );
    }
    announcedPageRef.current = page;
  }, [data]);

  return (
    <div className={"table-container " + style["occurrences-table"]}>
      <div className="table-scroll-wrapper" style={maxHeight ? { maxHeight, overflowY: "auto" } : undefined}>
        <table aria-label={tableLabel}>
          <thead>
            <tr>
              <th scope="col">URL</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && !data ? (
              <tr><td><span role="status">Loading occurrences…</span></td></tr>
            ) : items.length === 0 ? (
              <tr><td>No occurrences found.</td></tr>
            ) : (
              items.map((url, i) => (
                <tr key={`${page}-${i}-${url}`}>
                  <td>
                    <a href={url} target="_blank" rel="noopener noreferrer" aria-label={`Open ${url} in a new tab`}>
                      {url}
                    </a>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
      <div className="pagination" role="navigation" aria-label="Occurrences pagination">
        <div className="pagination-text">
          {`Showing ${items.length} of ${pagination.totalCount.toLocaleString()}`}
          {pagination.totalPages > 0 && ` (Page ${page + 1} of ${pagination.totalPages})`}
        </div>
        <div className="pagination-buttons">
          <div className="pagination-buttons-prev-next">
            <StyledButton onClick={() => setPage(0)} disabled={page === 0} label="First" variant="light" className="pagination-edge" />
            <StyledButton onClick={() => setPage((p) => Math.max(0, p - 1))} disabled={page === 0} label="Previous" variant="light" />
            <StyledButton onClick={() => setPage((p) => p + 1)} disabled={page >= pagination.totalPages - 1} label="Next" variant="light" />
            <StyledButton onClick={() => setPage(Math.max(0, pagination.totalPages - 1))} disabled={page >= pagination.totalPages - 1} label="Last" variant="light" className="pagination-edge" />
          </div>
        </div>
      </div>
    </div>
  );
};

// Drawer listing every occurrence of a blocker, with a "Copy as CSV" export —
// replaces the old hover-only tooltip, which was unreachable by keyboard and
// invisible to screen readers.
export const BlockerUrlsDrawer = ({
  auditId,
  isShared,
  contentHashId,
  occurrences,
  triggerLabel,
}: {
  auditId: string;
  isShared: boolean;
  contentHashId: string;
  occurrences: number;
  triggerLabel: string;
}) => {
  const [open, setOpen] = useState(false);
  // Bumped on each open to remount the table, so it starts back on page 1
  const [openCount, setOpenCount] = useState(0);
  const { copy, copying } = useCopyBlockerUrlsCsv({ auditId, isShared, contentHashId });

  return (
    <Drawer.Root
      direction="right"
      shouldScaleBackground
      setBackgroundColorOnScale={false}
      onOpenChange={(nextOpen) => {
        setOpen(nextOpen);
        if (nextOpen) setOpenCount((c) => c + 1);
      }}
    >
      <Drawer.Trigger
        render={(props) => (
          <StyledButton onClick={props.onClick} label={triggerLabel} variant="naked" aria-haspopup="dialog" />
        )}
      />
      <Drawer.Portal>
        <Drawer.Overlay className="drawer-overlay" />
        <Drawer.Content className="drawer-content">
          <div className="drawer-content-inner">
            <div className="drawer-header">
              <Drawer.Title render={<h4 />}>
                {`${occurrences.toLocaleString()} ${occurrences === 1 ? "Occurrence" : "Occurrences"}`}
              </Drawer.Title>
              <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                <StyledButton
                  onClick={copy}
                  label="Copy as CSV"
                  icon={<FaClipboard className="icon-small" />}
                  variant="light"
                  loading={copying}
                  loadingText="Copying..."
                />
                <Drawer.Close
                  render={(props) => (
                    <StyledButton onClick={props.onClick} label="Close" variant="light" icon={<FaTimes />} />
                  )}
                />
              </div>
            </div>
            <BlockerOccurrencesTable
              key={openCount}
              auditId={auditId}
              isShared={isShared}
              contentHashId={contentHashId}
              expectedCount={occurrences}
              enabled={open}
              maxHeight="50vh"
            />
          </div>
        </Drawer.Content>
      </Drawer.Portal>
    </Drawer.Root>
  );
};
