import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMsal } from '@azure/msal-react';
import { useGlobalStore } from '#src/utils';
import { usePostHog } from 'posthog-js/react';
import { useQueryClient } from '@tanstack/react-query';

export const SsoCallback = () => {
    const navigate = useNavigate();
    const { instance } = useMsal();
    const { setAuthenticated, setSsoAuthenticated, setLoading, setAnnounceMessage } = useGlobalStore();
    const posthog = usePostHog();
    const queryClient = useQueryClient();

    useEffect(() => {
        const handleCallback = async () => {
            setLoading('Completing SSO login...');
            try {
                const response = await instance.handleRedirectPromise();
                if (response) {
                    // Store SSO token TEMPORARILY to test backend validation
                    localStorage.setItem('sso_token', response.idToken);
                    
                    // Validate with backend BEFORE setting authenticated state
                    const claims: any = response.idTokenClaims;
                    try {
                        const API = await import('aws-amplify/api');
                        await API.get({
                            apiName: 'auth',
                            path: '/getAccount',
                        }).response;
                        
                        // Only set authenticated if backend validation succeeds
                        setAuthenticated(claims?.oid || claims?.sub);
                        setSsoAuthenticated(true);
                        posthog?.identify(claims?.oid || claims?.sub, { email: claims?.email });
                        
                        setAnnounceMessage("Login Success!", "success");
                        setTimeout(() => queryClient.refetchQueries({ queryKey: ['user'] }), 100);
                        navigate('/audits');
                    } catch (backendError: any) {
                        // Backend rejected the user - remove token and show error
                        localStorage.removeItem('sso_token');
                        console.error('Backend validation failed:', backendError);
                        
                        // Parse error message from response - AWS Amplify wraps errors differently
                        let errorMessage = 'You are not authorized to access Equalify. Please contact an administrator to request access.';
                        
                        // Check direct message property
                        if (backendError?.message) {
                            errorMessage = backendError.message;
                        }
                        // Check response body
                        else if (backendError?.response?.body) {
                            try {
                                const errorBody = backendError.response.body;
                                const parsed = typeof errorBody === 'string' ? JSON.parse(errorBody) : errorBody;
                                errorMessage = parsed?.message || errorMessage;
                            } catch (e) {
                                // Keep default error message
                            }
                        }
                        
                        // Navigate to login with error message
                        navigate('/login?error=' + encodeURIComponent(errorMessage));
                    }
                }
            } catch (error) {
                console.error('SSO callback error:', error);
                navigate('/login');
            } finally {
                setLoading(false);
            }
        };
        
        handleCallback();
    }, []);

    return <div>Processing SSO login...</div>;
};
