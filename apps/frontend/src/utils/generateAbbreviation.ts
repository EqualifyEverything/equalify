export default function generateAbbreviation(str: string): string {
  const words = str.trim().split(/\s+/); // Split by one or more spaces after trimming
  if (words.length < 2) {
    return words.length > 0 ? words[0].slice(0, 2) : ""; // Fallback for single/empty strings
  }
  
  // Take the first character of the first two words and join them
  const acronym = words[0][0] + words[1][0];
  return acronym.toUpperCase(); // Optionally convert to uppercase
}