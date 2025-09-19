import { useGlobalStore } from "#src/utils";
import { useEffect } from 'react';
import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { Loader } from ".";
import * as Auth from "aws-amplify/auth";
import { usePostHog } from 'posthog-js/react';
import { useUser } from '../queries';

export const Navigation = () => {
    const location = useLocation();
    const navigate = useNavigate();
    const { loading, authenticated, setAuthenticated, darkMode, setDarkMode } = useGlobalStore();
    const posthog = usePostHog();
    const { data: user } = useUser();

    useEffect(() => {
        window.scrollTo(0, 0);
    }, [location]);

    useEffect(() => {
        if (darkMode) {
            document.documentElement.classList.add('dark')
        }
        else {
            document.documentElement.classList.remove('dark')
        }
    }, [darkMode]);

    const authRoutes = ['/login'];

    useEffect(() => {
        const async = async () => {
            const attributes = (await Auth.fetchAuthSession()).tokens?.idToken?.payload;
            if (!attributes) {
                setAuthenticated(false);
                if (!authRoutes.includes(location.pathname) || location.pathname === '/') {
                    navigate(`/${location.search}`);
                }
            }
            else {
                setAuthenticated(attributes?.sub);
                posthog?.identify(attributes?.sub, { email: attributes?.email });
                await Auth.fetchAuthSession({ forceRefresh: true });
                if (location.pathname === '/') {
                    navigate('/dashboard')
                }
            }
        }
        async();
    }, []);

    return (
        <div>
            <div className='w-full max-w-screen-lg mx-auto bg-background p-4 min-h-[calc(100vh_-_120px)]'>
                <div className='flex flex-col sm:flex-row items-center justify-between mb-4'>
                    <Link to='/' className='relative hover:opacity-50'><img className='w-[150px]' src='/logo.svg' />
                    </Link>
                    <div className='flex flex-row items-center gap-4'>
                        {(!authenticated ? [
                            { label: 'Log In', value: '/login' },
                            { label: 'Sign Up', value: '/signup' },
                        ] : [
                            { label: 'Dashboard', value: '/dashboard' },
                            { label: 'Audits', value: '/audits' },
                            { label: 'Pages', value: '/pages' },
                            { label: 'Logs', value: '/logs' },
                            { label: 'Account', value: '/account' },
                            { label: 'Log Out', value: '/logout' },
                        ]).map(obj => <Link key={obj.value} to={obj.value} className={`hover:opacity-50 ${location.pathname === obj.value && 'font-bold'}`}>{obj.label}</Link>)}
                    </div>
                </div>
                {loading && <Loader />}
                <Outlet />
            </div>
            <div className='w-full max-w-screen-lg mx-auto py-4 flex flex-col sm:flex-row items-center justify-between'>
                <div>© {new Date().getFullYear()} Equalify. All rights reserved</div>
                {/* <button onClick={() => setDarkMode(!darkMode)}>{`Switch to ${darkMode ? 'Light Mode' : 'Dark Mode'}`}</button> */}
            </div>
        </div>
    )
}