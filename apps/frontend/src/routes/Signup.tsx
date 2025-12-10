import { useState } from 'react';
import * as Auth from 'aws-amplify/auth';
import * as API from 'aws-amplify/api';
import { Link, useNavigate } from 'react-router-dom';
import { sleep, useGlobalStore } from '#src/utils';
import { useQueryClient } from '@tanstack/react-query';
import { usePostHog } from 'posthog-js/react';
import { Logo } from '../components/Logo';
import styles from './Signup.module.scss';

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

    return (
        <form onSubmit={signup} className={`${styles.signup} form-control`}>
            <div className={styles.header}>
                <div className={styles.logo}>
                    <Logo />
                </div>
                <h1 className={`${styles.title} initial-focus-element`}>Sign up for Equalify</h1>
            </div>
            {import.meta.env.VITE_SSO_ENABLED ? (
                <div className={styles.actions}>
                    <p>Sorry, you may only login using your SSO provider.</p>
                    <div className={styles.authSwitch}>
                        <span>Already have an account? </span>
                        <Link to="/login" className={styles.authLink}>
                            Log in
                        </Link>
                    </div>
                </div>
            ) : (
                <>
                    <div className={styles.fields}>
                        <div className={`fieldGroup`}>
                            <label htmlFor='name' className={styles.label}>Name</label>
                            <input id='name' name='name' required type='text' placeholder='John Doe' className={styles.input} />
                        </div>
                        <div className={`fieldGroup`}>
                            <label htmlFor='email' className={styles.label}>Email address</label>
                            <input id='email' name='email' required type='email' placeholder='johndoe@example.com' className={styles.input} />
                        </div>
                        <div className={`fieldGroup`}>
                            <label htmlFor='password' className={styles.label}>Password</label>
                            <input id='password' name='password' required type='password' placeholder='Password' className={styles.input} />
                        </div>
                        <div className={`fieldGroup`}>
                            <input id='terms' name='terms' required type='checkbox' placeholder='terms' />
                            <label htmlFor='terms'>
                                I agree to the <a className='hover:opacity-50' target='_blank' href='https://equalify.app/terms-of-service/'>Terms of Service</a> and <a className='hover:opacity-50' target='_blank' href='https://equalify.app/privacy-policy/'>Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    <div className={styles.actions}>
                        <button disabled={loading} className={styles.button}>Sign Up</button>
                        {error && <div className={styles.error}>{error}</div>}
                        <p>
                            <span>Already have an account? </span>
                            <Link to="/login" className={styles.authLink}>
                                Log in
                            </Link>
                        </p>
                    </div>
                </>
            )}
        </form>
    )
}
