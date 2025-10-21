import { useState } from 'react';
import * as Auth from 'aws-amplify/auth';
import * as API from 'aws-amplify/api';
import { useNavigate } from 'react-router-dom';
import { sleep, useGlobalStore } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import { usePostHog } from 'posthog-js/react';

export const Signup = () => {
    const queryClient = useQueryClient();
    const { loading, setLoading, setAuthenticated } = useGlobalStore();
    const [error, setError] = useState('');
    const navigate = useNavigate();
    const posthog = usePostHog();

    const signup = async (e) => {
        e.preventDefault();
        const { name, email, password } = Object.fromEntries(new FormData(e.currentTarget));

        if (password.length < 6) {
            alert(`Your password must be at least 6 characters long.`);
            return;
        }

        setLoading(true);

        const { userExists } = await (await API.post({
            apiName: 'public', path: '/checkIfUserExists', options: { body: { email } }
        }).response).body.json();

        if (userExists) {
            setLoading(false);
            alert(`It looks like you already have an account- log in to renew your subscription.`);
            navigate(`/login?email=${encodeURIComponent(email)}`)
            return;
        }
        try {
            await Auth.signUp({
                username: crypto.randomUUID(),
                password: password,
                options: {
                    userAttributes: {
                        email: email,
                        name: name,
                    },
                    autoSignIn: { enabled: true }
                }
            });
            await sleep(500);
            await Auth.autoSignIn();
            const attributes = (await Auth.fetchAuthSession()).tokens?.idToken?.payload
            setAuthenticated(attributes?.sub);
            posthog?.identify(attributes?.sub, { email: attributes?.email });
            setLoading(false);
            setTimeout(() => {
                API.post({ apiName: 'auth', path: '/trackUser' }).response
                queryClient.refetchQueries({ queryKey: ['user'] })
            }, 1000);
            navigate('/audits');
        }
        catch (err) {
            console.log(err);
            setLoading(false);
            setError(err?.message);
        }
    }

    return (<form onSubmit={signup} className='flex flex-col gap-4 max-w-screen-sm'>
        <h1 className='mx-auto initial-focus-element'>Create an account</h1>
        <div className='flex flex-col'>
            <label htmlFor='name'>Name</label>
            <input id='name' name='name' required type='text' placeholder='John Doe' />
        </div>
        <div className='flex flex-col'>
            <label htmlFor='email'>Email address</label>
            <input id='email' name='email' required type='email' placeholder='johndoe@example.com' />
        </div>
        <div className='flex flex-col'>
            <label htmlFor='password'>Password</label>
            <input id='password' name='password' required type='password' placeholder='Password' />
        </div>
        <div className='flex flex-row gap-1'>
            <input id='terms' name='terms' required type='checkbox' placeholder='terms' />
            <label htmlFor='terms'>I agree to the <a className='hover:opacity-50' target='_blank' href='https://equalify.app/terms-of-service/'>Terms of Service</a> and <a className='hover:opacity-50' target='_blank' href='https://equalify.app/privacy-policy/'>Privacy Policy</a></label>
        </div>
        <button disabled={loading} className=''>Sign Up</button>
        {error && <div className=''>{error}</div>}
    </form>)
}