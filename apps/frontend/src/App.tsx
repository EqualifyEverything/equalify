import { Amplify } from 'aws-amplify';
import * as Auth from 'aws-amplify/auth'
import { createBrowserRouter, Link, RouterProvider } from 'react-router-dom';
import { Account, Audit, Audits, CreateAudit, Dashboard, Home, Log, Login, Logout, Logs, Page, Pages, Signup } from '#src/routes';
import { Navigation } from '#src/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PostHogProvider } from 'posthog-js/react'
import { CookieStorage } from 'aws-amplify/utils';
import { cognitoUserPoolsTokenProvider } from 'aws-amplify/auth/cognito';
const queryClient = new QueryClient();

Amplify.configure({
  Auth: {
    Cognito: {
      userPoolId: import.meta.env.VITE_USERPOOLID,
      userPoolClientId: import.meta.env.VITE_USERPOOLWEBCLIENTID,
    },
  },
  API: {
    GraphQL: {
      endpoint: `${import.meta.env.VITE_GRAPHQL_URL}/v1/graphql`,
      defaultAuthMode: 'none',
    },
    REST: {
      public: {
        endpoint: `${import.meta.env.VITE_API_URL}/public`,
      },
      auth: {
        endpoint: `${import.meta.env.VITE_API_URL}/auth`,
      },
    },
  },
}, {
  API: {
    GraphQL: {
      headers: async () => {
        const jwtToken = (await Auth.fetchAuthSession()).tokens?.idToken?.toString();
        return { ...(jwtToken && { Authorization: `Bearer ${jwtToken}` }) };
      },
    },
    REST: {
      headers: async ({ apiName }) => apiName === 'auth' ? { Authorization: `Bearer ${(await Auth.fetchAuthSession()).tokens?.idToken?.toString()}` } : { 'X-Api-Key': '1' },
      retryStrategy: { strategy: 'no-retry' },
    }
  },
});

cognitoUserPoolsTokenProvider.setKeyValueStorage(new CookieStorage({
  domain: location.hostname.endsWith('equalify.app') ? 'equalify.app' : location.hostname,
  secure: false,
  sameSite: 'lax'
}));

const router = createBrowserRouter([
  {
    path: '',
    element: <Navigation />,
    children: [
      { path: '', element: <Home /> },
      { path: 'login', element: <Login /> },
      { path: 'signup', element: <Signup /> },
      { path: 'dashboard', element: <Dashboard /> },
      { path: 'audits', element: <Audits /> },
      { path: 'audits/:auditId', element: <Audit /> },
      { path: 'create-audit', element: <CreateAudit /> },
      { path: 'pages', element: <Pages /> },
      { path: 'pages/:pageId', element: <Page /> },
      { path: 'logs', element: <Logs /> },
      { path: 'log/:logId', element: <Log /> },
      { path: 'account', element: <Account /> },
      { path: 'logout', element: <Logout /> },
    ],
    errorElement: <>
      <Navigation />
      <Link to='/' className='absolute top-[calc(50%_-_100px)] left-[calc(50%_-_200px)] text-text text-center w-[400px] hover:opacity-50'>
        404 - Page not found!
      </Link>
    </>
  },
]);

export const App = () => <PostHogProvider apiKey={import.meta.env.VITE_POSTHOG_KEY} options={{ api_host: 'https://us.posthog.com' }}>
  <QueryClientProvider client={queryClient}>
    <RouterProvider router={router} />
  </QueryClientProvider>
</PostHogProvider>