import { event } from "#src/utils";
import { fetchAndValidateRemoteCsv } from "#src/routes/internal/fetchAndValidateRemoteCsv";

//
// Fetches a remote CSV, checks for basic validity, and returns the parsed data or an error
//

export const fetchRemoteCsv = async () => {
  const csvUrl = (event.queryStringParameters as any).url;
  return await fetchAndValidateRemoteCsv(csvUrl);
};
