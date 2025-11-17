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
    const { instance, accounts } = useMsal();

    useEffect(() => {
        if (code && type) {
            setError(`You must login before you can verify your new ${type?.replace('_', ' ')}`)
        }
    }, [code, type]);

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
        try {
            const response = await instance.loginPopup({
                scopes: ["User.Read"]
            });
            
            // Store SSO token in localStorage (keep token in localStorage for security/API headers)
            localStorage.setItem('sso_token', response.idToken);
            
            // Set authenticated state
            const claims: any = response.idTokenClaims;
            setAuthenticated(claims?.oid || claims?.sub); // oid is Azure AD user ID
            setSsoAuthenticated(true); // Track that this is SSO auth in the store
            setLoading(false);
            posthog?.identify(claims?.oid || claims?.sub, { email: claims?.email });
            
            setAriaAnnounceMessage("Login Success!");
            navigate('/audits');
            
            setTimeout(() => queryClient.refetchQueries({ queryKey: ['user'] }), 100);
        } catch (error) {
            console.error(error);
            setLoading(false);
            setError('There was an issue logging in with SSO. Please try again.');
        }
    };


    return (<form onSubmit={login} className='flex flex-col gap-4 max-w-screen-sm'>
        <h1 className='mx-auto initial-focus-element'>Welcome back!</h1>
        {import.meta.env.VITE_SSO_ENABLED ? <button onClick={ssoLogin}>Sign In with SSO</button> : <>
            <div className='flex flex-col'>
                <label htmlFor='email'>Email address</label>
                <input id='email' name="email" required type="email" placeholder='johndoe@example.com' defaultValue={email ?? ''} />
            </div>
            <div className='flex flex-col'>
                <label htmlFor='password'>Password</label>
                <input id='password' name="password" required type="password" placeholder='Password' />
            </div>
            <button disabled={!!loading} className=''>Log In</button>
            {error && <div className=''>{error}</div>}
        </>}
    </form>)
}