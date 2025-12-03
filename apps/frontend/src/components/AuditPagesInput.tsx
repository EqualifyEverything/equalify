import { useEffect, useState } from "react";
import { useGlobalStore } from "../utils";
import { StyledButton } from "./StyledButton";

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
  const [pagesToDeleteCount, setPagesToDeleteCount] = useState(0);
  const [csvError, setCsvError] = useState<string | null>(null);

  const handleCsvUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.name.endsWith('.csv') && !file.type.includes('csv') && !file.type.includes('text')) {
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
      const lines = text.split(/\r?\n/).map(line => line.trim()).filter(line => line.length > 0);

      if (lines.length === 0) {
        setCsvError("No URLs found in the file");
        return;
      }

      const newPages: Page[] = [];
      const errors: string[] = [];
      const duplicates: string[] = [];

      lines.forEach((line, index) => {
        // Skip empty lines and potential header rows
        if (!line || line.toLowerCase().includes('url') && index === 0) return;

        // Validate and format URL
        const validUrl = validateAndFormatUrl(line);
        if (!validUrl) {
          errors.push(`Line ${index + 1}: Invalid URL format`);
          return;
        }

        // Check for duplicates in existing pages
        if (pages.some(page => page.url === validUrl)) {
          duplicates.push(validUrl);
          return;
        }

        // Check for duplicates in new pages being added
        if (newPages.some(page => page.url === validUrl)) {
          duplicates.push(validUrl);
          return;
        }

        newPages.push({ url: validUrl, type: "html" });
      });

      // Add all valid URLs to the pages
      if (newPages.length > 0) {
        setPages([...pages, ...newPages]);
        setAriaAnnounceMessage(`Successfully imported ${newPages.length} URL(s) from CSV`);
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
        setCsvError(messages.join(' '));
      }

      // Clear the file input
      e.target.value = '';
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

    // Validate and format URL
    const validUrl = validateAndFormatUrl(input);
    if (!validUrl) return;

    // Check for duplicates
    if (pages.some((page) => page.url === validUrl)) {
      setUrlError("This URL has already been added");
      return;
    }

    // Add page with default type of 'html'
    setPages([...pages, { url: validUrl, type: "html" }]);
    // Clear the input field
    const inputField = form.querySelector(
      '[name="pageInput"]'
    ) as HTMLInputElement;
    if (inputField) inputField.value = "";
    setUrlError(null);
    setAriaAnnounceMessage(`Added URL ${validUrl}!`);
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

  const removePage = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();
    const button = e.currentTarget;
    const form = button.closest("form");
    if (!form) return;

    const checkboxes = form.querySelectorAll(".page-checkbox:checked");
    const toRemove = Array.from(checkboxes).map(
      (cb) => (cb as HTMLInputElement).value
    );
    setPages(pages.filter((row) => !toRemove.includes(row.url)));

    setAriaAnnounceMessage(`Removed ${toRemove.length} URLs!`);
    uncheckAllPagesToDelete();
    setPagesToDeleteCount(0);
    return;
  };

  const updatePageType = (url: string, type: "html" | "pdf") => {
    setPages(
      pages.map((page) => (page.url === url ? { ...page, type } : page))
    );

    if (updateParentPageType) { //update in DB if we have a function to do so
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

      // Validate and format URL
      const validUrl = validateAndFormatUrl(inputValue);
      if (!validUrl) return;

      // Check for duplicates
      if (pages.some((page) => page.url === validUrl)) {
        setUrlError("This URL has already been added");
        return;
      }

      // Add page with default type of 'html'
      setPages([...pages, { url: validUrl, type: "html" }]);
      // Clear the input field
      input.value = "";
      setUrlError(null);
    }
  };

  const updatePagesSelectedCount = (e: React.ChangeEvent<HTMLInputElement>) => {
    const button = e.target;
    const form = button.closest("form");
    if (!form) return;
    const checkboxes = form.querySelectorAll(".page-checkbox:checked");
    //console.log(checkboxes);
    setPagesToDeleteCount(checkboxes.length);
  };

  //TODO this is a hack - we should really probably be using a controlled component
  const uncheckAllPagesToDelete = () => {
    var x = document.getElementsByClassName("page-checkbox");
    for (let i = 0; i < x.length; i++) {
      x[i].setAttribute("checked", "");
    }
  };

  // update parent value on change
  useEffect(() => {
    //console.log("Updating pages...");
    //console.log("Pages:", pages);
    //console.log("InitialPages", initialPages);

    if (returnMutation) {
      let arrDelta: Page[] = [];
      if (initialPages === pages) return;
      if (initialPages.length < pages.length) { // adding pages
        //console.log("Adding...")
        arrDelta = pages.filter(page => !initialPages.includes(page));
        if (addParentPages)
          addParentPages(arrDelta);
      }
      if (initialPages.length > pages.length) { // removing pages
        //console.log("Removing...")
        const initialUrls = initialPages.map(page => page.url);
        const pageUrls = pages.map(page => page.url);
        const overlap = initialUrls.filter(page => !pageUrls.includes(page));
        arrDelta = initialPages.filter(page => overlap.includes(page.url));
        //console.log("To remove:",arrDelta);
        if (removeParentPages)
          removeParentPages(arrDelta);
      }
      setParentPages(arrDelta);
    }
    else {
      setParentPages(pages);
    }
  }, [pages]);

  return (
    <>
      {!isShared &&
        <>
          <div className="flex flex-col">
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
          </div>
          {["URLs"].includes(importBy) && (
            <div className="flex flex-col">
              <label htmlFor="pageInput">URLs:</label>
              <input
                id="pageInput"
                name="pageInput"
                onKeyDown={handleUrlInputKeyDown}
                placeholder="example.com"
              />
              {urlError && <p className="text-red-500 text-sm mt-1">{urlError}</p>}
            </div>
          )}
          {["CSV"].includes(importBy) && (
            <div className="flex flex-col">
              <label htmlFor="csvInput">CSV Upload:</label>
              <input
                id="csvInput"
                name="csvInput"
                type="file"
                accept=".csv,.txt,text/csv,text/plain"
                onChange={handleCsvUpload}
              />
              <p className="text-sm text-gray-600 mt-1">Upload a CSV or text file with one URL per line</p>
              {csvError && <p className="text-red-500 text-sm mt-1">{csvError}</p>}
            </div>
          )}
          <StyledButton 
            label="Add Urls"
            onClick={addPage}
          />
        </>
      }
      {pages.length > 0 && (
        <>
          <h2>Review Added URLs</h2>
          <table>
            <thead>
              <tr>
                {!isShared && <th>Select</th>}
                <th>URL</th>
                <th>Scan Type</th>
              </tr>
            </thead>
            <tbody>
              {pages.map((page) => (
                <tr key={page.url}>
                  {!isShared && <td>
                    <input
                      id={page.url}
                      name={page.url}
                      type="checkbox"
                      className="page-checkbox"
                      value={page.url}
                      defaultChecked={false}
                      onChange={updatePagesSelectedCount}
                      aria-label={`Checkbox for ${page.url}`}
                    />
                  </td>}
                  <td>{page.url}</td>
                  <td>
                    <select
                      name={`pageType_${page.url}`}
                      value={page.type}
                      onChange={(e) =>
                        updatePageType(
                          page.url,
                          e.target.value as "html" | "pdf"
                        )
                      }
                      className="!p-0 mx-1"
                      disabled={isShared}
                    >
                      <option value="html">HTML</option>
                      <option value="pdf">PDF</option>
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {!isShared && pagesToDeleteCount > 0 ? (
            <button type="button" onClick={removePage}>
              Remove {pagesToDeleteCount} URL(s)
            </button>
          ) : null}
        </>
      )}
    </>
  );
};
