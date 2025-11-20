// Utility to check if error is JWT expiration
export const isJwtExpiredError = (error: any): boolean => {
  const errorMessage = error?.message || error?.toString() || '';
  const errorResponse = JSON.stringify(error?.response || error?.data || error?.errors || {});
  
  // Don't treat GraphQL field errors as JWT expiration
  if (errorMessage.includes('field') && errorMessage.includes('not found')) {
    return false;
  }
  
  return (
    errorMessage.includes('JWTExpired') ||
    errorMessage.includes('Could not verify JWT') ||
    errorMessage.includes('jwt expired') ||
    errorMessage.includes('Token has expired') ||
    errorResponse.includes('JWTExpired') ||
    errorResponse.includes('Could not verify JWT')
  );
};

// Utility to handle JWT expiration
export const handleJwtExpiration = () => {
  console.error('JWT expired, logging out...');
  localStorage.removeItem('sso_token');
  
  // Redirect to login with a message
  sessionStorage.setItem('auth_error', 'Your session has expired. Please log in again.');
  window.location.href = '/login';
};

// Wrapper for API calls to automatically handle JWT expiration
export const withJwtErrorHandling = async <T,>(apiCall: () => Promise<T>): Promise<T> => {
  try {
    return await apiCall();
  } catch (error) {
    if (isJwtExpiredError(error)) {
      handleJwtExpiration();
      throw error; // Re-throw to prevent further processing
    }
    throw error;
  }
};
