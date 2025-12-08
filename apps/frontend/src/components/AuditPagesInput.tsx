import { useEffect, useState } from "react";
import { useGlobalStore } from "../utils";
import { StyledButton } from "./StyledButton";
import { AuditPagesInputTable } from "./AuditPagesInputTable";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { Card } from "./Card";
import style from "./AuditPagesInput.module.scss";

interface Page {
  url: string;
  type: "html" | "pdf";
  id?: string;
}
interface ChildProps {
  initialPages: Page[];
  setParentPages: (newValue: Page[]) => void; // Callback function prop
  addParentPages?: (newValue: Page[]) => void; // Callback function prop
  removeParentPages?: (newValue: Page[]) => void; // Callback function prop
  updateParentPageType?: (newValue: Page) => void; // Callback function prop
  returnMutation?: boolean; // if true, only return changed rows
  isShared?: boolean;
}

export const AuditPagesInput: React.FC<ChildProps> = ({
  initialPages,
  setParentPages,
  addParentPages,
  removeParentPages,
  updateParentPageType,
  returnMutation = false,
  isShared = false,
}) => {
  const { setAriaAnnounceMessage } = useGlobalStore();

  const [importBy, setImportBy] = useState("URLs");
  const [urlError, setUrlError] = useState<string | null>(null);
  const [pages, setPages] = useState<Page[]>(initialPages);
  //const [pagesToDeleteCount, setPagesToDeleteCount] = useState(0);
  const [csvError, setCsvError] = useState<string | null>(null);

  const handleCsvUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (
      !file.name.endsWith(".csv") &&
      !file.type.includes("csv") &&
      !file.type.includes("text")
    ) {
      setCsvError("Please upload a CSV or text file");
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const text = event.target?.result as string;
      if (!text) {
        setCsvError("Failed to read file");
        return;
      }

      // Parse CSV - expecting one URL per line
      const lines = text
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

      if (lines.length === 0) {
        setCsvError("No URLs found in the file");
        return;
      }

      const newPages: Page[] = [];
      const errors: string[] = [];
      const duplicates: string[] = [];

      lines.forEach((line, index) => {
        // Skip empty lines and potential header rows
        if (!line || (line.toLowerCase().includes("url") && index === 0))
          return;

        // Parse line for URL and optional type (format: url,type)
        const { url: rawUrl, type: pageType } = parseUrlWithType(line);

        // Validate and format URL
        const validUrl = validateAndFormatUrl(rawUrl);
        if (!validUrl) {
          errors.push(`Line ${index + 1}: Invalid URL format`);
          return;
        }

        // Check for duplicates in existing pages
        if (pages.some((page) => page.url === validUrl)) {
          duplicates.push(validUrl);
          return;
        }

        // Check for duplicates in new pages being added
        if (newPages.some((page) => page.url === validUrl)) {
          duplicates.push(validUrl);
          return;
        }

        newPages.push({ url: validUrl, type: pageType });
      });

      // Add all valid URLs to the pages
      if (newPages.length > 0) {
        setPages(prev => [...prev, ...newPages]);
        setAriaAnnounceMessage(
          `Successfully imported ${newPages.length} URL(s) from CSV`
        );
        setCsvError(null);
      }

      // Show warnings if there were issues
      if (errors.length > 0 || duplicates.length > 0) {
        const messages: string[] = [];
        if (newPages.length > 0) {
          messages.push(`Successfully imported ${newPages.length} URL(s).`);
        }
        if (duplicates.length > 0) {
          messages.push(`${duplicates.length} duplicate(s) skipped.`);
        }
        if (errors.length > 0) {
          messages.push(`${errors.length} invalid URL(s) skipped.`);
        }
        setCsvError(messages.join(" "));
      }

      // Clear the file input
      e.target.value = "";
    };

    reader.onerror = () => {
      setCsvError("Error reading file");
    };

    reader.readAsText(file);
  };

  const addPage = (e: React.MouseEvent<HTMLButtonElement>) => {
    //e.preventDefault();
    const button = e.currentTarget;
    const form = button.closest("form");
    if (!form) return;

    const formData = new FormData(form);
    const input = formData.get("pageInput") as string;
    if (!input || input.length === 0) {
      return;
    }

    // Parse input for URL and optional type
    const { url: rawUrl, type: pageType } = parseUrlWithType(input);

    // Validate and format URL
    const validUrl = validateAndFormatUrl(rawUrl);
    if (!validUrl) return;

    // Check for duplicates
    if (pages.some((page) => page.url === validUrl)) {
      setUrlError("This URL has already been added");
      return;
    }

    // Add page with parsed type (defaults to 'html')
    setPages(prev => [...prev, { url: validUrl, type: pageType }]);
    // Clear the input field
    const inputField = form.querySelector(
      '[name="pageInput"]'
    ) as HTMLInputElement;
    if (inputField) inputField.value = "";
    setUrlError(null);
    setAriaAnnounceMessage(`Added URL ${validUrl}!`);
    //console.log(pages);
    return;
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
        setUrlError("Only HTTP and HTTPS URLs are supported");
        return null;
      }
      setUrlError(null);
      return urlObj.href;
    } catch {
      setUrlError(
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

  const downloadCsvTemplate = () => {
    const csvContent = `url,type
https://example.com,html
https://example.com/about,html
https://example.com/document.pdf,pdf
https://example.com/contact,html`;
    
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "url-import-template.csv");
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  const removePages = (pagesToRemove: Page[]) => {
    //console.log("After Removal:", pages.filter((row) => !pagesToRemove.includes(row)));
    setPages(pages.filter((row) => !pagesToRemove.includes(row)));
    setAriaAnnounceMessage(`Removed ${pagesToRemove.length} URLs!`);
    return;
  };

  const updatePageType = (url: string, type: "html" | "pdf") => {
    //console.log("updatePagesType...", pages);
    setPages(
      pages.map((page) => (page.url === url ? { ...page, type } : page))
    ); 

    if (updateParentPageType) {
      //update in DB if we have a function to do so
      updateParentPageType({ url: url, type: type });
    }
  };

  const handleUrlInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const input = e.currentTarget;
      const form = input.closest("form");
      if (!form) return;

      const inputValue = input.value;
      if (!inputValue || inputValue.length === 0) {
        return;
      }

      // Parse input for URL and optional type
      const { url: rawUrl, type: pageType } = parseUrlWithType(inputValue);

      // Validate and format URL
      const validUrl = validateAndFormatUrl(rawUrl);
      if (!validUrl) return;

      // Check for duplicates
      if (pages.some((page) => page.url === validUrl)) {
        setUrlError("This URL has already been added");
        return;
      }

      // Add page with parsed type (defaults to 'html')
      setPages(prev => [...prev, { url: validUrl, type: pageType }]);
      // Clear the input field
      input.value = "";
      setUrlError(null);
    }
  };

  // update parent value on change
  useEffect(() => {
    //console.log("Updating pages...", pages);
    //console.log("InitialPages", initialPages);

    if (returnMutation) { // return only the delta, ie modified URLs
      let arrDelta: Page[] = [];
      if (initialPages === pages) return;
      if (initialPages.length < pages.length) {
        // adding pages
        //console.log("Adding...")
        arrDelta = pages.filter((page) => !initialPages.includes(page));
        if (addParentPages) addParentPages(arrDelta);
      }
      if (initialPages.length > pages.length) {
        // removing pages
        //console.log("Removing...")
        const initialUrls = initialPages.map((page) => page.url);
        const pageUrls = pages.map((page) => page.url);
        const overlap = initialUrls.filter((page) => !pageUrls.includes(page));
        arrDelta = initialPages.filter((page) => overlap.includes(page.url));
        //console.log("To remove:",arrDelta);
        if (removeParentPages) removeParentPages(arrDelta);
      }
      setParentPages(arrDelta);
    } else {
      setParentPages(pages);
    }
  }, [pages]);
  //console.log(pages);

  return (
    <div className={style.AuditPagesInput}>
      {/* {pages.length > 0 && ( */}
        <>
          <AuditPagesInputTable
            pages={pages}
            removePages={removePages}
            isShared={isShared}
            updatePageType={updatePageType}
          />
          {!isShared && (
            <Card variant="inset-light">
              <h3>Add URLs to Scan</h3>
              <div className={style["input-area"]}>
                <StyledLabeledInput>
                  <label htmlFor="importBy">Import By:</label>
                  <select
                    id="importBy"
                    name="importBy"
                    value={importBy}
                    onChange={(e) => setImportBy(e.target.value)}
                  >
                    <option>URLs</option>
                    <option>CSV</option>
                  </select>
                </StyledLabeledInput>
                {["URLs"].includes(importBy) && (
                  <div>
                    <StyledLabeledInput>
                      <label htmlFor="pageInput">URLs:</label>
                      <input
                        id="pageInput"
                        name="pageInput"
                        onKeyDown={handleUrlInputKeyDown}
                        placeholder="example.com"
                      />
                    </StyledLabeledInput>
                    {urlError && <p>{urlError}</p>}
                  </div>
                )}
                {["CSV"].includes(importBy) && (
                  <div>
                    <StyledLabeledInput>
                      <label htmlFor="csvInput">CSV Upload:</label>
                      <input
                        id="csvInput"
                        name="csvInput"
                        type="file"
                        accept=".csv,.txt,text/csv,text/plain"
                        onChange={handleCsvUpload}
                      />
                    </StyledLabeledInput>
                    <p className="font-small">
                      Upload a CSV with one URL per line. Optionally specify type as <code>url,type</code> (e.g., <code>https://example.com/doc.pdf,pdf</code>). If no type is provided, HTML is assumed.
                    </p>
                    <StyledButton
                      label="Download Template CSV"
                      onClick={downloadCsvTemplate}
                      variant="secondary"
                    />
                    {csvError && (
                      <p className="text-red-500 text-sm mt-1">{csvError}</p>
                    )}
                  </div>
                )}
              </div>
              <div className={style["button-area"]}>
              <StyledButton label="Add Urls" onClick={addPage} />
              </div>
            </Card>
          )}
        </>
      {/* )} */}
    </div>
  );
};
