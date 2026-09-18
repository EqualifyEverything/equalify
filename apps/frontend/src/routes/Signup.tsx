import { useState } from 'react';
import * as Auth from 'aws-amplify/auth';
import * as API from 'aws-amplify/api';
import { Link, useNavigate } from 'react-router-dom';
import { sleep, useGlobalStore, trackSession } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import styles from "./Signup.module.scss";
import { Logo } from "#src/components/Logo";
import { StyledButton } from "#src/components/StyledButton";

export const Signup = () => {
    const queryClient = useQueryClient();
    const { loading, setLoading, setAuthenticated } = useGlobalStore();
    const [error, setError] = useState('');
    const [requestSubmitted, setRequestSubmitted] = useState('');
    const navigate = useNavigate();
    const isSso = !!import.meta.env.VITE_SSO_ENABLED;

    const requestAccess = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const { name, email } = Object.fromEntries(new FormData(e.currentTarget));
        setError('');
        setLoading(true);
        try {
            const response = (await (await API.post({
                apiName: 'public', path: '/requestAccess', options: { body: { name: String(name), email: String(email) } }
            }).response).body.json()) as any;
            if (response?.status === 'success') {
                setRequestSubmitted(response?.message ?? 'Request submitted! An administrator will review it shortly.');
            }
            else {
                setError(response?.message ?? 'Something went wrong — please try again.');
            }
        }
        catch (err) {
            console.log(err);
            setError('Something went wrong — please try again.');
        }
        setLoading(false);
    }
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
            setLoading(false);
            setTimeout(() => {
                API.post({ apiName: 'auth', path: '/trackUser' }).response
                trackSession()
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

    return (<form onSubmit={isSso ? requestAccess : signup} className={styles.signup}>
        <div className={styles.header}>
            <div className={styles.logo}>
                <Logo />
            </div>
            <h1 className={`${styles.title} initial-focus-element`}>{isSso ? 'Request Access to Equalify' : 'Sign up for Equalify'}</h1>
        </div>

        {isSso ? (requestSubmitted ? <div className={`${styles.signUpForm}`} role="status">
            <p>{requestSubmitted}</p>
        </div> : <div className={`${styles.signUpForm}`}>
            <p>Enter your institutional email address and an administrator will review your request.</p>
            <label htmlFor='name'>Name</label>
            <input id='name' name='name' required type='text' placeholder='John Doe' />
            <label htmlFor='email'>Email address</label>
            <input id='email' name='email' required type='email' placeholder='johndoe@uic.edu' />
            {error && <div className={`${styles.error}`} role="alert">{error}</div>}
            <StyledButton
                variant='green'
                type='submit'
                onClick={undefined}
                label={loading ? `Submitting...` : `Request Access`}
                disabled={loading}
            />
        </div>) : <>
        <div className={`${styles.signUpForm}`}>
            <label htmlFor='name'>Name</label>
            <input id='name' name='name' required type='text' placeholder='John Doe' />
            <label htmlFor='email'>Email address</label>
            <input id='email' name='email' required type='email' placeholder='johndoe@example.com' />
            <label htmlFor='password'>Password</label>
            <input id='password' name='password' required type='password' placeholder='Password' />
            <div className={`${styles.terms}`}>
                <input id='terms' name='terms' required type='checkbox' placeholder='terms' />
                <label htmlFor='terms'>I agree to the <a target='_blank' href='https://equalify.app/terms-of-service/'>Terms of Service</a> and <a target='_blank' href='https://equalify.app/privacy-policy/'>Privacy Policy</a>.</label>
            </div>
            {error && <div className={`${styles.error}`}>{error}</div>}

            <StyledButton
                variant='green'
                type='submit'
                onClick={undefined}
                label={`Sign Up`}
            />
            
        </div>
        </>}
        <p>
            <span>Already have an account? </span>
            <Link to="/login" className={styles.authLink}>
                Log in
            </Link>
        </p>

    </form>)
}