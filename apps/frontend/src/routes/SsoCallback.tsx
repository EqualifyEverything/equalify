import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMsal } from '@azure/msal-react';
import { useGlobalStore } from '#src/utils';
import { usePostHog } from 'posthog-js/react';
import { useQueryClient } from '@tanstack/react-query';

export const SsoCallback = () => {
    const navigate = useNavigate();
    const { instance } = useMsal();
    const { setAuthenticated, setSsoAuthenticated, setLoading, setAriaAnnounceMessage } = useGlobalStore();
    const posthog = usePostHog();
    const queryClient = useQueryClient();

    useEffect(() => {
        const handleCallback = async () => {
            setLoading('Completing SSO login...');
            try {
                const response = await instance.handleRedirectPromise();
                if (response) {
                    // Store SSO token in localStorage
                    localStorage.setItem('sso_token', response.idToken);
                    
                    // Set authenticated state
                    const claims: any = response.idTokenClaims;
                    setAuthenticated(claims?.oid || claims?.sub);
                    setSsoAuthenticated(true);
                    posthog?.identify(claims?.oid || claims?.sub, { email: claims?.email });
                    
                    setAriaAnnounceMessage("Login Success!");
                    setTimeout(() => queryClient.refetchQueries({ queryKey: ['user'] }), 100);
                    navigate('/audits');
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
