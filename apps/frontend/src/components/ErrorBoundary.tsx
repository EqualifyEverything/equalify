import { useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useGlobalStore } from '../utils';
import { isJwtExpiredError } from '../utils/jwtErrorHandler';

export const GlobalErrorHandler = () => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const { setAuthenticated, setSsoAuthenticated, setAriaAnnounceMessage } = useGlobalStore();
  const logoutTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    const handleError = (error: any) => {
      // Check if it's a JWT expiration error
      if (isJwtExpiredError(error)) {
        console.error('JWT expired error detected, waiting 5 seconds before logout...');
        
        // Clear any existing timeout
        if (logoutTimeoutRef.current) {
          clearTimeout(logoutTimeoutRef.current);
        }
        
        // Wait 5 seconds before actually logging out
        logoutTimeoutRef.current = setTimeout(() => {
          console.error('JWT still expired after 5 seconds, logging out...');
          
          // Clear auth state
          localStorage.removeItem('sso_token');
          setAuthenticated(false);
          setSsoAuthenticated(false);
          
          // Clear all queries
          queryClient.clear();
          
          // Show message and redirect
          setAriaAnnounceMessage('Your session has expired. Please log in again.');
          navigate('/login');
        }, 5000);
      }
    };

    // Set up global error handler for the query client
    const unsubscribe = queryClient.getQueryCache().subscribe((event) => {
      if (event.type === 'observerResultsUpdated') {
        const query = event.query;
        if (query.state.error) {
          handleError(query.state.error);
        }
      }
    });

    // Also handle mutation errors
    const mutationUnsubscribe = queryClient.getMutationCache().subscribe((event) => {
      if (event.type === 'updated' && event.mutation.state.error) {
        handleError(event.mutation.state.error);
      }
    });

    // Set up global error handler for uncaught promise rejections
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      handleError(event.reason);
    };

    window.addEventListener('unhandledrejection', handleUnhandledRejection);

    return () => {
      if (logoutTimeoutRef.current) {
        clearTimeout(logoutTimeoutRef.current);
      }
      unsubscribe();
      mutationUnsubscribe();
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);
    };
  }, [queryClient, navigate, setAuthenticated, setSsoAuthenticated, setAriaAnnounceMessage]);

  return null;
};
