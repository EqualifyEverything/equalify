import { useEffect, useState } from "react";
import { useGlobalStore } from "../utils";
import style from "./AuditRemoteCsvInput.module.scss";
import { Card } from "./Card";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { useDebouncedCallback } from "use-debounce";

import * as API from "aws-amplify/api";

interface Page {
  url: string;
  type: "html" | "pdf";
  id?: string;
}
interface ChildProps {
  csvUrl: string
  setCsvUrl: (value: React.SetStateAction<string>) => void
}

// test file: https://equalify.app/wp-content/uploads/2026/02/url-import-template.csv

export const AuditRemoteCsvInput: React.FC<ChildProps> = ({ csvUrl, setCsvUrl
}) => {
  const { setAnnounceMessage } = useGlobalStore();
  const [error, setError] = useState<string | null>("");

  /**
   * Normalize a URL by removing trailing slashes from the path
   * This ensures URLs like "https://example.com/" and "https://example.com" are treated as the same
   */
  const normalizeUrl = (url: string): string => {
    // Remove trailing slash unless it's just the root path
    return url.replace(/\/+$/, '') || url;
  };

  const validateAndFormatUrl = (input: string): string | null => {
    // Trim whitespace
    let url = input.trim();
    if (!url) return null;

    // Add https:// if no protocol is specified
    if (!url.match(/^https?:\/\//i)) {
      url = "https://" + url;
    }

    // Validate URL format
    try {
      const urlObj = new URL(url);
      // Check if it's http or https
      if (!["http:", "https:"].includes(urlObj.protocol)) {
        setError("Only HTTP and HTTPS URLs are supported");
        return null;
      }
      setError(null);
      // Normalize the URL to remove trailing slashes
      return normalizeUrl(urlObj.href);
    } catch {
      setError(
        "Invalid URL format. Please enter a valid URL (e.g., example.com or https://example.com)"
      );
      return null;
    }
  };

  /**
   * Parse a line that may contain a URL and optional type separated by comma
   * Format: url,type (e.g., "https://example.com,html" or "https://example.com/doc.pdf,pdf")
   * If no type specified, defaults to "html"
   */
  const parseUrlWithType = (line: string): { url: string; type: "html" | "pdf" } => {
    const trimmedLine = line.trim();

    // Check if the line ends with ,html or ,pdf (case-insensitive)
    const htmlMatch = trimmedLine.match(/^(.+),\s*html\s*$/i);
    const pdfMatch = trimmedLine.match(/^(.+),\s*pdf\s*$/i);

    if (pdfMatch) {
      return { url: pdfMatch[1].trim(), type: "pdf" };
    }
    if (htmlMatch) {
      return { url: htmlMatch[1].trim(), type: "html" };
    }

    // No type specified, default to html
    return { url: trimmedLine, type: "html" };
  };


  useEffect(() => {
    console.log("Fetching and validating CSV...", csvUrl);
    fetchAndValidateCsv()
  }, [csvUrl])

  const fetchAndValidateCsv = async () => {

    const response = await API.get({
      apiName: "auth",
      path: "/fetchAndValidateRemoteCsv",
      options: {
        queryParams: { url: csvUrl.trim() },
      },
    }).response;
    const resp = (await response.body.json()) as any;
    console.log(resp);
  }

  return (
    <div className={style.AuditRemoteCsvInput}>
      <Card variant="inset-light">
        <h3>Use a Remote CSV Integration</h3>
        <p className="font-small">
          Or, provide a link to a hosted CSV to automatically sync your URL list before every scan.
        </p>
        <StyledLabeledInput>
          <label htmlFor="remote-csv-url">URL of Remote CSV</label>
          <input id="remote-csv-url" type="url" value={csvUrl} onChange={(e) => { setCsvUrl(e.target.value) }} />
        </StyledLabeledInput>
        {error ? error : null}
      </Card>
    </div>
  );
};
