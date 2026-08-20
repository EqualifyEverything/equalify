// A search string wrapped in double quotes (e.g. `"https://example.com/"`) matches
// the URL exactly; otherwise it's treated as a partial, substring match.
export const buildUrlSearchClause = (searchString: string) => {
  const quotedMatch = searchString.match(/^"(.*)"$/);
  if (quotedMatch) {
    return { url: { url: { _ilike: quotedMatch[1] } } };
  }
  return { url: { url: { _ilike: `%${searchString}%` } } };
};
