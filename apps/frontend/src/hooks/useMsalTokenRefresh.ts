import { useEffect } from 'react';
import { useMsal } from '@azure/msal-react';

export const useMsalTokenRefresh = () => {
  const { instance } = useMsal();

  useEffect(() => {
    const ssoToken = localStorage.getItem('sso_token');
    
    // Only run if we have an SSO token
    if (!ssoToken) {
      return;
    }

    const refreshToken = async () => {
      try {
        const currentAccounts = instance.getAllAccounts();
        if (currentAccounts.length === 0) {
          console.log('No MSAL accounts loaded, skipping token refresh');
          return;
        }

        const account = currentAccounts[0];
        const response = await instance.acquireTokenSilent({
          scopes: ['openid', 'profile', 'email'],
          account: account,
        });

        // Update the stored token
        if (response.idToken) {
          localStorage.setItem('sso_token', response.idToken);
          console.log('SSO token refreshed successfully');
        }
      } catch (error: any) {
        console.error('Token refresh failed:', error);
        // Don't logout here - let the error boundary handle it if it's a real auth issue
      }
    };

    // Refresh token every 10 minutes (before the 15-minute expiry)
    const intervalId = setInterval(refreshToken, 10 * 60 * 1000);

    return () => clearInterval(intervalId);
  }, [instance]);
};
