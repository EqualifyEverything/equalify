import { useState, useEffect } from 'react';
import * as Auth from 'aws-amplify/auth';
import {  Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { useGlobalStore } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import { usePostHog } from 'posthog-js/react';
import { useMsal } from '@azure/msal-react';
import { Logo } from "#src/components/Logo";
import { StyledButton } from "#src/components/StyledButton";
import styles from "./Login.module.scss";


export const Login = () => {
    const queryClient = useQueryClient();
    const { loading, setLoading, setAuthenticated, setSsoAuthenticated, setAnnounceMessage } = useGlobalStore();
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
                setAnnounceMessage("Login Success!", "success");
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
            setAnnounceMessage(
                confirm?.error?.message ?? `There was an issue logging in. Please try again.`, "error"
            );
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
            setAnnounceMessage("Login Success!", "success");
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
            setAnnounceMessage(errorMessage, "error");
        }
    };


    return (
    <form onSubmit={login} className={styles.login}>
        <div className={styles.header}>
            <div className={styles.logo}>
                <Logo />
            </div>
            <h1 className={`${styles.title} initial-focus-element`}>Sign in to Equalify</h1>
        </div>

        {import.meta.env.VITE_SSO_ENABLED ? <>
            {error && <div className={`${styles.error}`}>{error}</div>}
            <StyledButton
                variant='green'
                onClick={ssoLogin}
                label={`Sign in with SSO`}
            />
        </> : <>
            <div className={`${styles.signInForm}`}>
                <label htmlFor='email'>Email address</label>
                <input id='email' name="email" required type="email" placeholder='johndoe@example.com' defaultValue={email ?? ''} />
                <label htmlFor='password'>Password</label>
                <input id='password' name="password" required type="password" placeholder='Password' />
                <StyledButton
                    variant='green'
                    onClick={``}
                    label={`Log In`}
                />
                {error && <div className={`${styles.error}`}>{error}</div>}
            </div>
        </>}
        <p>
            <span>New here? </span>
            <Link to="/signup" className={styles.authLink}>
                Create an account
            </Link>
        </p>
    </form>)
}