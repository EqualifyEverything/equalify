import { useState } from "react";
import { useGlobalStore } from "../utils";
import style from "./AuditCrawlInput.module.scss";
import { Card } from "./Card";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { StyledButton } from "./StyledButton";
import { AuditPagesInputTable } from "./AuditPagesInputTable";

import * as API from "aws-amplify/api";
import { MdCheckCircle, MdError } from "react-icons/md";
import { TbAlertTriangle } from "react-icons/tb";
import { LuSearch } from "react-icons/lu";

const URL_SOFT_LIMIT = 10_000;

interface Page {
  url: string;
  type: "html" | "pdf";
  id?: string;
}
interface ChildProps {
  pages: Page[];
  setParentPages: (newValue: Page[]) => void;
}

export const AuditCrawlInput: React.FC<ChildProps> = ({
  pages,
  setParentPages,
}) => {
  const { setAnnounceMessage } = useGlobalStore();
  const [crawlUrl, setCrawlUrl] = useState("");
  const [isCrawling, setIsCrawling] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [crawlMethod, setCrawlMethod] = useState<string | null>(null);
  const [discoveredPages, setDiscoveredPages] = useState<Page[]>([]);

  const handleCrawl = async (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();
    if (!crawlUrl.trim()) return;

    setIsCrawling(true);
    setError(null);
    setCrawlMethod(null);
    setDiscoveredPages([]);
    setParentPages([]);

    try {
      const response = await API.post({
        apiName: "auth",
        path: "/crawlUrl",
        options: { body: { url: crawlUrl.trim() } },
      }).response;

      const result = (await response.body.json()) as any;

      if (result.error) {
        setError(result.error);
        return;
      }

      setCrawlMethod(result.method);
      const discoveredPages: Page[] = (result.urls || []).map((url: string) => ({
        url,
        type: url.toLowerCase().endsWith(".pdf") ? "pdf" as const : "html" as const,
      }));

      setDiscoveredPages(discoveredPages);
      setParentPages(discoveredPages);
      setAnnounceMessage(
        `Found ${discoveredPages.length} URL(s) via ${result.method}!`,
        "success"
      );
    } catch (err) {
      setError("Failed to crawl site. Please check the URL and try again.");
    } finally {
      setIsCrawling(false);
    }
  };

  return (
    <div className={style.AuditCrawlInput}>
      <Card variant="inset-light">
        <h3>Crawl a Website</h3>
        <p className="font-small">
          Enter a website URL to automatically discover pages via its sitemap.
        </p>
        <StyledLabeledInput>
          <label htmlFor="crawl-url">Website URL</label>
          <input
            id="crawl-url"
            type="url"
            placeholder="example.com"
            value={crawlUrl}
            onChange={(e) => setCrawlUrl(e.target.value)}
          />
        </StyledLabeledInput>
        <StyledButton
          icon={<LuSearch />}
          label={isCrawling ? "Crawling..." : "Crawl Site"}
          onClick={handleCrawl}
          disabled={!crawlUrl.trim() || isCrawling}
          loading={isCrawling}
        />
        {error && (
          <Card variant="short-error">
            <MdError className="icon-small" />
            <div className="font-small">
              <b>Crawl failed.</b> {error}
            </div>
          </Card>
        )}
        {crawlMethod && discoveredPages.length > 0 && (
          <Card variant="short-success">
            <MdCheckCircle className="icon-small" />
            <div className="font-small">
              <b>
                Found {discoveredPages.length} URL(s)
              </b>{" "}
              via {crawlMethod}.
              {pages.length < discoveredPages.length && (
                <> ({pages.length} selected)</>
              )}
            </div>
          </Card>
        )}
        {discoveredPages.length >= URL_SOFT_LIMIT && (
          <Card variant="short-error">
            <TbAlertTriangle className="icon-small" />
            <div className="font-small">
              <b>Large audit:</b> This site has{" "}
              {discoveredPages.length.toLocaleString()} URLs. Large audits take
              significantly longer to scan.
            </div>
          </Card>
        )}
        {discoveredPages.length > 0 && (
          <AuditPagesInputTable
            pages={discoveredPages}
            removePages={() => {}}
            isShared={false}
            updatePageType={(url, type) => {
              const updated = discoveredPages.map((p) =>
                p.url === url ? { ...p, type } : p
              );
              setDiscoveredPages(updated);
            }}
            onSelectionChange={(selectedPages) => {
              setParentPages(selectedPages);
            }}
          />
        )}
      </Card>
    </div>
  );
};
