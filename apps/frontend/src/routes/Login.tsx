import { useState, useEffect } from 'react';
import * as Auth from 'aws-amplify/auth';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { useGlobalStore } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import { usePostHog } from 'posthog-js/react';
import { useMsal } from '@azure/msal-react';

export const Login = () => {
    const queryClient = useQueryClient();
    const { loading, setLoading, setAuthenticated, setSsoAuthenticated, setAriaAnnounceMessage } = useGlobalStore();
    const [error, setError] = useState('');
    const location = useLocation();
    const navigate = useNavigate();
    const posthog = usePostHog();
    const [searchParams, setSearchParams] = useSearchParams();
    const email = searchParams.get('email');
    const code = searchParams.get('code');
    const type = searchParams.get('type');
    const errorParam = searchParams.get('error');
    const { instance, accounts } = useMsal();

    useEffect(() => {
        if (code && type) {
            setError(`You must login before you can verify your new ${type?.replace('_', ' ')}`)
        }
        if (errorParam) {
            setError(errorParam);
        }
    }, [code, type, errorParam]);

    const login = async (e) => {
        e.preventDefault();
        setError('');
        setLoading('Logging in...');
        const { email, password } = Object.fromEntries(new FormData(e.currentTarget));
        try {
            await Auth.signIn({
                username: email.toString(),
                password: password.toString(),
            });
            const attributes = (await Auth.fetchAuthSession()).tokens?.idToken?.payload;
            setAuthenticated(attributes?.sub);
            setLoading(false);
            posthog?.identify(attributes?.sub, { email: attributes?.email });

            if (code) {
                navigate(`/verify/${location.search}`);
            }
            else {
                setAriaAnnounceMessage("Login Success!");
                navigate('/audits');
            }

            const hasuraClaims = JSON.parse(attributes?.['https://hasura.io/jwt/claims']);
            if (hasuraClaims?.['x-hasura-default-role'] === 'cancelled') {
                navigate('/subscriptions');
            }
            setTimeout(() => queryClient.refetchQueries({ queryKey: ['user'] }), 100);
        }
        catch (err) {
            console.log(err);
            setLoading(false);
            setError(confirm?.error?.message ?? `There was an issue logging in. Please try again.`);
        }
    }

    const ssoLogin = async () => {
        setLoading('Logging in with SSO...');
        setError(''); // Clear any previous errors
        
        let response;
        try {
            response = await instance.loginPopup({
                scopes: ["User.Read"]
            });
        } catch (popupError: any) {
            // This catches SSO popup errors only
            console.error('SSO login popup error:', popupError);
            console.error('Error code:', popupError?.errorCode);
            console.error('Error name:', popupError?.name);
            console.error('Error message:', popupError?.message);
            
            // Don't show error for user cancellation or interaction_in_progress
            const errorCode = popupError?.errorCode || popupError?.name || '';
            const errorMessage = popupError?.message || '';
            
            if (errorCode.includes('user_cancelled') || 
                errorCode.includes('interaction_in_progress') ||
                errorMessage.includes('interaction_in_progress') ||
                errorCode === 'BrowserAuthError') {
                console.log('Ignoring expected SSO error:', errorCode);
                setLoading(false);
                return;
            }
            
            console.error('Showing SSO popup error to user');
            setLoading(false);
            setError('There was an issue logging in with SSO. Please try again.');
            return;
        }
        
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
            
            setLoading(false);
            setAriaAnnounceMessage("Login Success!");
            navigate('/audits');
            
            setTimeout(() => queryClient.refetchQueries({ queryKey: ['user'] }), 100);
        } catch (backendError: any) {
            // Backend rejected the user - remove token and show error
            localStorage.removeItem('sso_token');
            setLoading(false);
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
            
            setError(errorMessage);
        }
    };


    return (<form onSubmit={login} className='flex flex-col gap-4 max-w-screen-sm'>
        <h1 className='mx-auto initial-focus-element'>Welcome back!</h1>
        {import.meta.env.VITE_SSO_ENABLED ? <>
            <button type="button" onClick={ssoLogin}>Sign In with SSO</button>
            {error && <div className='text-red-600 dark:text-red-400'>{error}</div>}
        </> : <>
            <div className='flex flex-col'>
                <label htmlFor='email'>Email address</label>
                <input id='email' name="email" required type="email" placeholder='johndoe@example.com' defaultValue={email ?? ''} />
            </div>
            <div className='flex flex-col'>
                <label htmlFor='password'>Password</label>
                <input id='password' name="password" required type="password" placeholder='Password' />
            </div>
            <button disabled={!!loading} className=''>Log In</button>
            {error && <div className='text-red-600 dark:text-red-400'>{error}</div>}
        </>}
    </form>)
}