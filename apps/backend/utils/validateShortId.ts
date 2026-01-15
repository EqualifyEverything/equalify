export const validateShortId = (id: string): boolean => {
  const shortIdRegex = /^[A-Z]{3}[0-9]{3}$/;
  return shortIdRegex.test(id);
};