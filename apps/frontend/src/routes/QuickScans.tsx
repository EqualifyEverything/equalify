import { useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { formatId, useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { Link } from "react-router-dom";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { Card } from "#src/components/Card.tsx";
import { DataRow } from "#src/components/DataRow.tsx";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { SkeletonAuditGrid } from "#src/components/Skeleton.tsx";
import { LuZap, LuSearch } from "react-icons/lu";
import { createLog } from "#src/utils/createLog.ts";
import styles from "./QuickScans.module.scss";

export const QuickScans = () => {
  const { setAnnounceMessage } = useGlobalStore();
  const queryClient = useQueryClient();

  const [url, setUrl] = useState("");
  const [type, setType] = useState<"html" | "pdf">("html");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data: quickScans, isLoading } = useQuery({
    queryKey: ["quickScans"],
    queryFn: async () => {
      const response = await (
        await API.get({
          apiName: "auth",
          path: "/getQuickScans",
        }).response
      ).body.json();
      return (response as any)?.body ?? response;
    },
    refetchInterval: 15000, // Poll every 15s for scan status updates
  });

  const formatUrl = (input: string): string | null => {
    let u = input.trim();
    if (!u) return null;
    if (!u.match(/^https?:\/\//i)) {
      u = "https://" + u;
    }
    try {
      const urlObj = new URL(u);
      if (!["http:", "https:"].includes(urlObj.protocol)) return null;
      return urlObj.href.replace(/\/+$/, "") || urlObj.href;
    } catch {
      return null;
    }
  };

  const runQuickScan = async () => {
    const formattedUrl = formatUrl(url);
    if (!formattedUrl) {
      setAnnounceMessage("Please enter a valid URL", "error");
      return;
    }

    setIsSubmitting(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/saveQuickScan",
          options: { body: { url: formattedUrl, type } },
        }).response
      ).body.json()) as { id: string };

      setAnnounceMessage("Quick scan started!", "success");
      await createLog("Quick scan started", response.id);
      setUrl("");
      queryClient.invalidateQueries({ queryKey: ["quickScans"] });
    } catch (err) {
      setAnnounceMessage("Failed to start quick scan", "error");
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className={styles.QuickScans}>
      <div className={styles["quick-scans-header"]}>
        <h1 className="initial-focus-element">Quick Scans</h1>
      </div>

      <Card variant="light">
        <h2>
          <LuZap className="icon-small" />
          Scan a URL
        </h2>
        <p className="font-small" style={{ marginBottom: "12px" }}>
          Run a single-page accessibility scan. Enter a URL to check one page for blockers.
        </p>
        <div className={styles["quick-scan-form"]}>
          <StyledLabeledInput className={styles["url-input"]}>
            <label htmlFor="quickScanUrl">URL:</label>
            <input
              id="quickScanUrl"
              type="text"
              placeholder="example.com"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  runQuickScan();
                }
              }}
            />
          </StyledLabeledInput>

          <StyledLabeledInput className={styles["type-select"]}>
            <label htmlFor="quickScanType">Type:</label>
            <select
              id="quickScanType"
              value={type}
              onChange={(e) => setType(e.target.value as "html" | "pdf")}
            >
              <option value="html">HTML</option>
              <option value="pdf">PDF</option>
            </select>
          </StyledLabeledInput>

          <StyledButton
            icon={<LuSearch />}
            onClick={runQuickScan}
            label="Scan"
            variant="dark"
            disabled={!url.trim() || isSubmitting}
            loading={isSubmitting}
            loadingText="Scanning..."
          />
        </div>
      </Card>

      <div className={styles["quick-scans-list"]}>
        <h2>Scan History</h2>
        {isLoading ? (
          <SkeletonAuditGrid count={3} />
        ) : quickScans?.length > 0 ? (
          <div className="cards-33">
            {quickScans.map((scan: any, index: number) => (
              <Card variant="light" key={index}>
                <Link to={`/quick-scans/${formatId(scan.id)}`}>
                  <div className={styles["scan-card"]}>
                    <div>
                      <div className={styles["scan-url"]}>{scan.url}</div>
                      <div className={styles["scan-meta"]}>
                        <span
                          className={`${styles["scan-status"]} ${
                            styles[scan.scan_status] || ""
                          }`}
                        >
                          {scan.scan_status === "processing"
                            ? `Scanning${
                                scan.scan_percentage
                                  ? ` (${scan.scan_percentage}%)`
                                  : "..."
                              }`
                            : scan.scan_status === "complete"
                            ? "Complete"
                            : scan.scan_status || "Pending"}
                        </span>
                        <span>{scan.type?.toUpperCase()}</span>
                      </div>
                    </div>
                  </div>
                </Link>
                <div style={{ marginTop: "8px" }}>
                  <DataRow
                    variant="highlight"
                    the_key="Blockers"
                    the_value={scan.blocker_count ?? "—"}
                  />
                  <DataRow
                    the_key="Scanned"
                    the_value={
                      scan.scan_updated_at
                        ? prettyDate(scan.scan_updated_at) +
                          " at " +
                          prettyTime(scan.scan_updated_at)
                        : "Not scanned yet"
                    }
                  />
                  <DataRow
                    variant="no-border"
                    the_key="Created"
                    the_value={prettyDate(scan.created_at)}
                  />
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <Card variant="light">
            <div className={styles["empty-state"]}>
              <p>No quick scans yet. Enter a URL above to get started!</p>
            </div>
          </Card>
        )}
      </div>
    </div>
  );
};

function prettyDate(dateTime: string) {
  return new Date(dateTime).toLocaleDateString("en-US", {
    weekday: "short",
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function prettyTime(dateTime: string) {
  const time = new Date(dateTime);
  return time.toLocaleTimeString(navigator.language, {
    hour: "2-digit",
    minute: "2-digit",
  });
}
