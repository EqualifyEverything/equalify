//
// Fetches a remote CSV, checks for basic validity, and returns the parsed data or an error
//

interface urlCsv {
  url: string;
  type: string;
}

export const fetchAndValidateRemoteCsv = async (csvUrl:string) => {
  try {
    if(!csvUrl) throw new Error(`Invalid CSV URL: ${csvUrl}`)
    const response = await fetch(csvUrl);

    if (!response.ok) {
      throw new Error(`Error fetching CSV: ${response.statusText}`);
    }

    const text = await response.text();
    const rows = text
      .split("\n")
      .map((line) => line.trim())
      .filter((line) => line.length > 0)
      .splice(1); // remove header row

    const parsedData: urlCsv[] = [];

    for (const [index, row] of rows.entries()) {
      const columns = row.split(",").map((col) => col.trim());

      if (columns.length !== 2) {
        throw new Error(
          `Invalid format at line ${index + 1}: Expected "url, type" but found ${columns.length} columns.`,
        );
      }

      const [url, type] = columns;

      try {
        new URL(url);
      } catch {
        throw new Error(`Invalid URL at line ${index + 1}: "${url}"`);
      }

      parsedData.push({ url, type });
    }

    return { success: true, url: csvUrl, data: parsedData };
  } catch (error) {
    return {
      success: false,
      url: csvUrl,
      error:
        error instanceof Error ? error.toString() : new Error("An unknown error occurred").toString(),
    };
  }
};
