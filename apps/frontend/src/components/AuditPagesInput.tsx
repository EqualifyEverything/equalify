import { useEffect, useState } from "react";
import { useGlobalStore } from "../utils";

interface Page {
  url: string;
  type: "html" | "pdf";
}
interface ChildProps {
  initialPages: Page[];
  setParentPages: (newValue: Page[]) => void; // Callback function prop
}

export const AuditPagesInput: React.FC<ChildProps> = ({
  initialPages,
  setParentPages,
}) => {
  const { setAriaAnnounceMessage } = useGlobalStore();

  const [importBy, setImportBy] = useState("URLs");
  const [urlError, setUrlError] = useState<string | null>(null);
  const [pages, setPages] = useState<Page[]>(initialPages);
  const [pagesToDeleteCount, setPagesToDeleteCount] = useState(0);

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

  const removePage = (e:React.MouseEvent<HTMLButtonElement>) => {
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
    console.log(checkboxes);
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
      console.log("Updating pages...");
      setParentPages(pages);
    }, [pages]);

  return (
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
          <label htmlFor="pageInput">CSV Upload:</label>
          <input id="pageInput" name="pageInput" type="file" />
        </div>
      )}
      <button type="button" onClick={addPage}>
        Add Pages
      </button>

      {pages.length > 0 && (
        <>
          <h2>Review Added Pages</h2>
          <table>
            <thead>
              <tr>
                <th>Select</th>
                <th>URL</th>
                <th>Scan Type</th>
              </tr>
            </thead>
            <tbody>
              {pages.map((page) => (
                <tr key={page.url}>
                  <td>
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
                  </td>
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
                    >
                      <option value="html">HTML</option>
                      <option value="pdf">PDF</option>
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {pagesToDeleteCount > 0 ? (
            <button type="button" onClick={removePage}>
              Remove {pagesToDeleteCount} Pages
            </button>
          ) : null}
        </>
      )}
    </>
  );
};
