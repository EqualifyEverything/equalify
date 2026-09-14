import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { useEffect, useRef, useState, ChangeEvent } from "react";
import { Link, useSearchParams } from "react-router-dom";
import * as Tooltip from "@radix-ui/react-tooltip";
import { Drawer } from "vaul-base";
import { FaClipboard, FaCode, FaTimes } from "react-icons/fa";
import { TbEye, TbEyeX } from "react-icons/tb";
import { PrismLight as SyntaxHighlighter } from "react-syntax-highlighter";
import jsx from "react-syntax-highlighter/dist/esm/languages/prism/jsx";
import { a11yDark as prism } from "react-syntax-highlighter/dist/esm/styles/prism";
import { StyledButton } from "./StyledButton";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { SkeletonAuditHeader, SkeletonBlockersTable } from "./Skeleton";
import { useGlobalStore } from "../utils";
import { useScrollFade } from "#src/utils/useScrollFade.ts";
import { getAccessibilityStandardLabel } from "#src/utils/accessibilityStandardTags.ts";
import { useToggleIgnore } from "../hooks";
import style from "./BlockersRecommendations.module.scss";

SyntaxHighlighter.registerLanguage("jsx", jsx);

const PAGE_SIZE = 10;
const URLS_TO_SHOW_IN_TOOLTIP = 10;
const STANDARDS_TO_SHOW = 1;

interface RecommendationTag {
  id: string;
  content: string;
}

interface Recommendation {
  id: string;             // representative blocker id
  short_id: string;
  content_hash_id: string;
  occurrences: number;
  urlCount: number;
  urls: string[];         // capped to 10 server-side; urlCount has the real total
  ignored: boolean;
  url: string;
  type: string;
  content: string;
  message: string;
  category: string;
  tags: RecommendationTag[];
}

interface RecommendationsResp {
  scan_date: string | null;
  stats: {
    totalBlockers: number;
    uniqueBlockers: number;
    activeUniqueBlockers: number;
    repeatedBlockers: number;
    pagesWithBlockers: number;
    filteredUnique: number;
    filteredRepeated: number;
    filteredSingle: number;
    filteredTotalOccurrences: number;
    filteredRepeatedOccurrences: number;
  };
  items: Recommendation[];
  pagination: {
    page: number;
    pageSize: number;
    totalCount: number;
    totalPages: number;
  };
}

interface BlockersRecommendationsProps {
  auditId: string;
  isShared: boolean;
}

export const BlockersRecommendations = ({ auditId, isShared }: BlockersRecommendationsProps) => {
  const { setAnnounceMessage, authenticated } = useGlobalStore();
  const [searchParams] = useSearchParams();
  const [scrollFadeRef, showScrollFade] = useScrollFade();

  // Local (not URL) page state so it doesn't collide with the Detailed
  // View's ?page= param when a user flips between tabs.
  const [page, setPage] = useState(0);
  const [status, setStatus] = useState<string>("active");
  const [repeat, setRepeat] = useState<"all" | "repeated" | "single">("all");

  useEffect(() => {
    setPage(0);
  }, [auditId]);

  const toggleIgnoreMutation = useToggleIgnore(auditId);

  const { data, isLoading, error } = useQuery({
    queryKey: ["auditRecommendations", auditId, page, status, repeat],
    queryFn: async () => {
      const response = await API.get({
        apiName: isShared ? "public" : "auth",
        path: "/getAuditRecommendations",
        options: {
          queryParams: {
            id: auditId,
            page: page.toString(),
            pageSize: PAGE_SIZE.toString(),
            status,
            repeat,
          },
        },
      }).response;
      return (await response.body.json()) as any as RecommendationsResp;
    },
    placeholderData: (previousData) => previousData,
  });

  // Announce pagination changes once the requested page has actually loaded.
  const announcedPageRef = useRef(page);
  useEffect(() => {
    if (!data?.pagination) return;
    if (announcedPageRef.current !== page) {
      setAnnounceMessage(
        `Showing ${data.items.length} of ${data.pagination.totalCount} recommendations, page ${page + 1} of ${data.pagination.totalPages}`,
        "normal",
        true
      );
    }
    announcedPageRef.current = page;
  }, [data]);

  const handleStatusChange = (value: string) => {
    setStatus(value);
    setPage(0);
  };
  const handleRepeatChange = (value: "all" | "repeated" | "single") => {
    setRepeat(value);
    setPage(0);
  };

  const getCategoryBlockersLinkSearch = (category: string) => {
    const params = new URLSearchParams(searchParams);
    params.set("view", "detailed");
    params.set("categories", category);
    params.delete("page");
    return params.toString();
  };

  const copyToClipboard = async (val: string) => {
    try {
      await navigator.clipboard.writeText(val);
      setAnnounceMessage(`"${val}" copied to clipboard!`, "success");
    } catch (err) {
      console.error("Failed to copy: ", err);
    }
  };

  const getElementTagFromContent = (content: string) => {
    const parser = new DOMParser();
    const nodeName = parser.parseFromString(content, "text/html").body?.firstChild?.nodeName.toLowerCase();
    return nodeName && nodeName !== "#text" ? `<${nodeName}>` : undefined;
  };

  if (error) {
    return (
      <div className={style.BlockersRecommendations}>
        <p>Error loading recommendations: {String(error)}</p>
      </div>
    );
  }

  if (isLoading || !data) {
    return (
      <div className={style.BlockersRecommendations}>
        <SkeletonAuditHeader />
        <SkeletonBlockersTable rows={5} />
      </div>
    );
  }

  // Default every stat so a backend that predates a field (e.g. mid-deploy)
  // renders zeros instead of throwing inside render and blanking the page.
  const stats = {
    totalBlockers: data.stats?.totalBlockers ?? 0,
    uniqueBlockers: data.stats?.uniqueBlockers ?? 0,
    activeUniqueBlockers: data.stats?.activeUniqueBlockers ?? 0,
    repeatedBlockers: data.stats?.repeatedBlockers ?? 0,
    pagesWithBlockers: data.stats?.pagesWithBlockers ?? 0,
    filteredUnique: data.stats?.filteredUnique ?? 0,
    filteredRepeated: data.stats?.filteredRepeated ?? 0,
    filteredSingle: data.stats?.filteredSingle ?? 0,
    filteredTotalOccurrences: data.stats?.filteredTotalOccurrences ?? 0,
    filteredRepeatedOccurrences: data.stats?.filteredRepeatedOccurrences ?? 0,
  };
  // "Fixing these clears 14% of your blockers" for the Repeated block
  const repeatedPercent = stats.filteredTotalOccurrences > 0
    ? Math.round((stats.filteredRepeatedOccurrences / stats.filteredTotalOccurrences) * 100)
    : 0;
  const repeatedNote = stats.filteredRepeated > 0 && stats.filteredTotalOccurrences > 0
    ? <>Fixing these clears <strong>{repeatedPercent}%</strong> of your blockers.</>
    : null;
  const items = data.items ?? [];
  const pagination = data.pagination ?? { page: 0, pageSize: PAGE_SIZE, totalCount: 0, totalPages: 0 };
  const auditIdNoDash = auditId.replace(/-/g, "");

  return (
    <div className={style.BlockersRecommendations}>
      {/* Stat blocks — these ARE the repeat filter */}
      <div className={style["stat-blocks"]} role="group" aria-label="Filter recommendations by how often they repeat">
        {([
          {
            key: "all",
            count: stats.filteredUnique,
            label: "Unique Blockers",
            description: "Every distinct blocker, counted once, no matter how many pages it appears on.",
            note: null,
          },
          {
            key: "repeated",
            count: stats.filteredRepeated,
            label: "Repeated Blockers",
            description: "Appear in more than one place. Fix the source once and every copy clears.",
            note: repeatedNote,
          },
          {
            key: "single",
            count: stats.filteredSingle,
            label: "One-off Blockers",
            description: "Appear in a single place. Each one is its own fix.",
            note: null,
          },
        ] as const).map((block) => (
          <button
            key={block.key}
            type="button"
            className={style["stat-block"] + (repeat === block.key ? " " + style["selected"] : "")}
            aria-pressed={repeat === block.key}
            onClick={() => handleRepeatChange(block.key)}
          >
            <span className={style["stat-count"]}>{block.count.toLocaleString()}</span>
            <span className={style["stat-label"]}>{block.label}</span>
            <span className={style["stat-description"]}>{block.description}</span>
            {block.note && <span className={style["stat-note"]}>{block.note}</span>}
          </button>
        ))}
      </div>

      <p className={style["explainer"]}>
        {stats.totalBlockers === 0 ? (
          <>The latest scan found no blockers, so there is nothing to recommend yet.</>
        ) : stats.repeatedBlockers === 0 ? (
          <>
            The latest scan found <strong>{stats.totalBlockers.toLocaleString()}</strong> {stats.totalBlockers === 1 ? "blocker" : "blockers"} across{" "}
            <strong>{stats.pagesWithBlockers.toLocaleString()}</strong> {stats.pagesWithBlockers === 1 ? "page" : "pages"}.
            None of them repeat, so each one is its own fix.
          </>
        ) : (
          <>
            The latest scan found <strong>{stats.totalBlockers.toLocaleString()}</strong> blockers across{" "}
            <strong>{stats.pagesWithBlockers.toLocaleString()}</strong> {stats.pagesWithBlockers === 1 ? "page" : "pages"}.
            Some of those are the same piece of code showing up in more than one place, so we grouped them.
            That leaves <strong>{stats.uniqueBlockers.toLocaleString()}</strong> unique blockers to fix.
            Fix those and all <strong>{stats.totalBlockers.toLocaleString()}</strong> go away.
            The list below puts the fixes that clear the most blockers first.
            Ignoring a blocker here ignores every copy of it.
          </>
        )}
      </p>

      {/* Status + result count */}
      <div className={style["filters-row"]}>
        <div className={style["filters"]}>
          <StyledLabeledInput>
            <label>Status</label>
            <select
              id="recommendationsStatus"
              aria-label="Filter recommendations by status:"
              value={status}
              onChange={(e: ChangeEvent<HTMLSelectElement>) => handleStatusChange(e.target.value)}
            >
              <option value="active">Active</option>
              <option value="ignored">Ignored</option>
              <option value="all">All</option>
            </select>
          </StyledLabeledInput>
        </div>
        <div className={style["result-count"]} aria-live="polite">
          {pagination.totalCount.toLocaleString()} {pagination.totalCount === 1 ? "recommendation" : "recommendations"}
        </div>
      </div>

      {/* Table */}
      <div className="table-container">
        <div
          className={"table-scroll-wrapper" + (showScrollFade ? " scroll-fade-active" : "")}
          ref={scrollFadeRef}
          tabIndex={0}
          role="region"
          aria-label="Recommendations table, scrollable horizontally"
        >
          <table aria-label="Recommendations table">
            <thead>
              <tr>
                <th scope="col">Impact</th>
                <th scope="col">Description</th>
                <th scope="col">Code</th>
                <th scope="col">Found on</th>
                <th scope="col">ID</th>
                <th scope="col">Ignore</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td colSpan={6} className={style["empty-table"]}>
                    {stats.totalBlockers === 0
                      ? "No blockers found in the latest scan."
                      : "No recommendations match these filters."}
                  </td>
                </tr>
              ) : (
                items.map((item) => {
                  const standards = item.tags
                    .map((tag) => {
                      const label = getAccessibilityStandardLabel(tag.content);
                      return label ? { id: tag.id, label } : null;
                    })
                    .filter((tag): tag is { id: string; label: string } => tag !== null);
                  const elementTag = getElementTagFromContent(item.content);
                  const placesText = `${item.occurrences} ${item.occurrences === 1 ? "time" : "times"} across ${item.urlCount} ${item.urlCount === 1 ? "page" : "pages"}`;

                  return (
                    <tr key={item.content_hash_id}>
                      {/* Impact */}
                      <td className={style["impact-cell"]}>
                        <div className={style["impact"]}>
                          {item.occurrences > 1 ? (
                            <Tooltip.Provider>
                              <Tooltip.Root>
                                <Tooltip.Trigger
                                  className={style["impact-chip"]}
                                  aria-label={`This fix reaches ${placesText}`}
                                >
                                  {`×${item.occurrences}`}
                                </Tooltip.Trigger>
                                <Tooltip.Portal>
                                  <Tooltip.Content side="bottom" className="tooltip" collisionPadding={8}>
                                    <div>
                                      <p>{`Appears ${placesText}:`}</p>
                                      <ul>
                                        {item.urls.slice(0, URLS_TO_SHOW_IN_TOOLTIP).map((u) => (
                                          <li key={u}>{u}</li>
                                        ))}
                                      </ul>
                                      {item.urlCount > URLS_TO_SHOW_IN_TOOLTIP && (
                                        <p>{`+${item.urlCount - URLS_TO_SHOW_IN_TOOLTIP} more pages`}</p>
                                      )}
                                    </div>
                                    <Tooltip.Arrow className="tooltip-arrow" />
                                  </Tooltip.Content>
                                </Tooltip.Portal>
                              </Tooltip.Root>
                            </Tooltip.Provider>
                          ) : (
                            <span className={`${style["impact-chip"]} ${style["single"]}`}>×1</span>
                          )}
                          <span className={style["impact-text"]}>
                            {item.urlCount === 1 ? "1 page" : `${item.urlCount} pages`}
                          </span>
                        </div>
                      </td>

                      {/* Description */}
                      <td className={style["description-cell"]}>
                        <div className={style["description"]}>
                          <div className={style["description-text"]}>{item.message}</div>
                          <div className={"tags " + style["tag-row"]}>
                            <Link
                              to={{ search: getCategoryBlockersLinkSearch(item.category) }}
                              className="tag"
                              aria-label={`View all ${item.category} blockers in Detailed View`}
                            >
                              {item.category}
                            </Link>
                            {standards.slice(0, STANDARDS_TO_SHOW).map((tag) => (
                              <span key={tag.id} className="tag">{tag.label}</span>
                            ))}
                            {standards.length > STANDARDS_TO_SHOW && (
                              <Tooltip.Provider>
                                <Tooltip.Root>
                                  <Tooltip.Trigger
                                    className={style["tooltip-rondel"]}
                                    aria-label={`${standards.length - STANDARDS_TO_SHOW} more accessibility standards`}
                                  >
                                    {`+${standards.length - STANDARDS_TO_SHOW}`}
                                  </Tooltip.Trigger>
                                  <Tooltip.Portal>
                                    <Tooltip.Content side="bottom" className="tooltip" collisionPadding={8}>
                                      <div className={"tags " + style["tag-row"]}>
                                        {standards.slice(STANDARDS_TO_SHOW).map((tag) => (
                                          <span key={tag.id} className="tag">{tag.label}</span>
                                        ))}
                                      </div>
                                      <Tooltip.Arrow className="tooltip-arrow" />
                                    </Tooltip.Content>
                                  </Tooltip.Portal>
                                </Tooltip.Root>
                              </Tooltip.Provider>
                            )}
                          </div>
                        </div>
                      </td>

                      {/* Code */}
                      <td className={style["code-cell"]}>
                        {elementTag && (
                          <div className={style["code-element"]}>
                            <span>Element:</span>
                            <code>{elementTag}</code>
                          </div>
                        )}
                        <Drawer.Root direction="right" shouldScaleBackground setBackgroundColorOnScale={false}>
                          <Drawer.Trigger className={style["view-code-button"]}>
                            <FaCode /> View Code
                          </Drawer.Trigger>
                          <Drawer.Portal>
                            <Drawer.Overlay className="drawer-overlay" />
                            <Drawer.Content className="drawer-content">
                              <div className="drawer-content-inner">
                                <div className="drawer-header">
                                  <h4>Blocker Code</h4>
                                  <Drawer.Close
                                    render={(props) => (
                                      <StyledButton onClick={props.onClick} label="Close" variant="light" icon={<FaTimes />} />
                                    )}
                                  />
                                </div>
                                <SyntaxHighlighter style={prism} language={"jsx"} className="drawer-code">
                                  {item.content}
                                </SyntaxHighlighter>
                              </div>
                            </Drawer.Content>
                          </Drawer.Portal>
                        </Drawer.Root>
                      </td>

                      {/* Found on */}
                      <td className={style["where-cell"]}>
                        <div className={style["where"]}>
                          <a href={item.url} target="_blank" rel="noopener noreferrer">{item.url}</a>
                          {item.urlCount > 1 && (
                            <span className={style["where-more"]}>
                              {`and ${item.urlCount - 1} other ${item.urlCount - 1 === 1 ? "page" : "pages"}`}
                            </span>
                          )}
                        </div>
                      </td>

                      {/* ID */}
                      <td className={style["id-td"]}>
                        <div className={style["id-cell"]}>
                          <Link to={"/shared/" + auditIdNoDash + "/" + item.short_id}>{item.short_id}</Link>
                          <StyledButton
                            onClick={() => copyToClipboard(item.short_id)}
                            icon={<FaClipboard className="icon-small" />}
                            label={item.short_id || "N/A"}
                            variant={"naked"}
                            showLabel={false}
                          />
                        </div>
                      </td>

                      {/* Ignore */}
                      <td className={style["ignore-cell"]}>
                        <StyledButton
                          onClick={() => {
                            if (!authenticated) return;
                            toggleIgnoreMutation.mutate({
                              blockerId: item.id,
                              contentHashId: item.content_hash_id,
                              isCurrentlyIgnored: item.ignored,
                            });
                            setAnnounceMessage(
                              `Blocker ${item.short_id} and its ${item.occurrences - 1} ${item.occurrences - 1 === 1 ? "copy" : "copies"} set to ${item.ignored ? "Active" : "Ignored"}`,
                              "success"
                            );
                          }}
                          label={item.ignored ? "Ignored" : "Active"}
                          icon={item.ignored ? <TbEyeX className="icon-small" /> : <TbEye className="icon-small" />}
                          variant={item.ignored ? "toggle-ignored" : "toggle"}
                        />
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="pagination" role="navigation" aria-label="Recommendations pagination">
          <div className="pagination-text">
            Showing {items.length} of {pagination.totalCount} recommendations
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
  );
};
