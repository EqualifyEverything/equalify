export const validateShortId = (id: string): boolean => {
  const shortIdRegex = /^([A-Z]{3}[0-9]{3}|[A-Z]{4}[0-9]{4})$/;
  return shortIdRegex.test(id);
};