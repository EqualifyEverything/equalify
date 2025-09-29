import { useState, useEffect } from 'react';
import * as Auth from 'aws-amplify/auth';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { useGlobalStore } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import { usePostHog } from 'posthog-js/react';

export const Login = () => {
    const queryClient = useQueryClient();
    const { loading, setLoading, setAuthenticated } = useGlobalStore();
    const [error, setError] = useState('');
    const location = useLocation();
    const navigate = useNavigate();
    const posthog = usePostHog();
    const [searchParams, setSearchParams] = useSearchParams();
    const email = searchParams.get('email');
    const code = searchParams.get('code');
    const type = searchParams.get('type');

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

    return (<form onSubmit={login} className='flex flex-col gap-4 max-w-screen-sm'>
        <h1 className='mx-auto'>Welcome back!</h1>
        <div className='flex flex-col'>
            <label htmlFor='email'>Email address</label>
            <input id='email' name="email" required type="email" placeholder='johndoe@example.com' defaultValue={email ?? ''} />
        </div>
        <div className='flex flex-col'>
            <label htmlFor='password'>Password</label>
            <input id='password' name="password" required type="password" placeholder='Password' />
        </div>
        <button disabled={loading} className=''>Log In</button>
        {error && <div className=''>{error}</div>}
    </form>)
}