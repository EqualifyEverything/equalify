import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { useState } from "react";
import { Drawer } from "vaul-base";
import { FaClipboard, FaTimes } from "react-icons/fa";
import { StyledButton } from "./StyledButton";
import { useGlobalStore } from "../utils";

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

const csvEscape = (value: string) => (/[",\r\n]/.test(value) ? `"${value.replace(/"/g, '""')}"` : value);

// CSV export needs every URL, not just the page on screen, so this loops the
// same endpoint at its max page size until it has them all.
const fetchAllBlockerUrls = async (auditId: string, isShared: boolean, contentHashId: string): Promise<string[]> => {
  const all: string[] = [];
  let page = 0;
  while (true) {
    const response = await API.get({
      apiName: isShared ? "public" : "auth",
      path: "/getBlockerUrls",
      options: {
        queryParams: {
          id: auditId,
          content_hash_id: contentHashId,
          page: page.toString(),
          pageSize: URLS_CSV_FETCH_SIZE.toString(),
        },
      },
    }).response;
    const json = (await response.body.json()) as any as BlockerUrlsResp;
    all.push(...json.urls);
    if (page + 1 >= json.pagination.totalPages) break;
    page += 1;
  }
  return all;
};

// Drawer listing every occurrence of a blocker (one row per occurrence, so a
// URL repeats if the blocker shows up more than once on it), paginated, with
// a "Copy as CSV" export — replaces the old hover-only tooltip, which was
// unreachable by keyboard and invisible to screen readers.
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
  const { setAnnounceMessage } = useGlobalStore();
  const [open, setOpen] = useState(false);
  const [page, setPage] = useState(0);
  const [copying, setCopying] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ["blockerUrls", auditId, contentHashId, page],
    queryFn: async () => {
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getBlockerUrls",
        options: {
          queryParams: {
            id: auditId,
            content_hash_id: contentHashId,
            page: page.toString(),
            pageSize: URLS_PAGE_SIZE.toString(),
          },
        },
      }).response;
      return (await response.body.json()) as any as BlockerUrlsResp;
    },
    enabled: open,
    placeholderData: (previousData) => previousData,
  });

  const handleCopyCsv = async () => {
    setCopying(true);
    try {
      const urls = await fetchAllBlockerUrls(auditId, isShared, contentHashId);
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

  const items = data?.urls ?? [];
  const pagination = data?.pagination ?? {
    page: 0,
    pageSize: URLS_PAGE_SIZE,
    totalCount: occurrences,
    totalPages: Math.ceil(occurrences / URLS_PAGE_SIZE),
  };

  return (
    <Drawer.Root
      direction="right"
      shouldScaleBackground
      setBackgroundColorOnScale={false}
      onOpenChange={(nextOpen) => {
        setOpen(nextOpen);
        if (nextOpen) setPage(0);
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
                  onClick={handleCopyCsv}
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
            <div className="table-container">
              <div className="table-scroll-wrapper" style={{ maxHeight: "50vh", overflowY: "auto" }}>
                <table aria-label="Occurrences of this blocker">
                  <thead>
                    <tr>
                      <th scope="col">URL</th>
                    </tr>
                  </thead>
                  <tbody>
                    {isLoading && !data ? (
                      <tr><td role="status">Loading occurrences…</td></tr>
                    ) : items.length === 0 ? (
                      <tr><td>No occurrences found.</td></tr>
                    ) : (
                      items.map((url, i) => (
                        <tr key={`${page}-${i}-${url}`}>
                          <td><a href={url} target="_blank" rel="noopener noreferrer">{url}</a></td>
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
          </div>
        </Drawer.Content>
      </Drawer.Portal>
    </Drawer.Root>
  );
};
