import { Amplify } from "aws-amplify";
import * as Auth from "aws-amplify/auth";
import { createBrowserRouter, Link, RouterProvider } from "react-router-dom";
import {
  Account,
  Audit,
  Audits,
  BuildAudit,
  Home,
  Log,
  Login,
  Logout,
  Logs,
  Signup,
  SsoCallback,
} from "#src/routes";
import { Navigation } from "#src/components";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { PostHogProvider } from "posthog-js/react";
import { CookieStorage } from "aws-amplify/utils";
import { cognitoUserPoolsTokenProvider } from "aws-amplify/auth/cognito";
import { registerSW } from "virtual:pwa-register";
import { useGlobalStore } from "./utils/useGlobalStore";
import { isJwtExpiredError, handleJwtExpiration } from "./utils/jwtErrorHandler";
registerSW({ immediate: true });

import { Toaster } from "sonner";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error: any) => {
        // Don't retry on JWT expiration errors
        if (isJwtExpiredError(error)) {
          handleJwtExpiration();
          return false;
        }
        return failureCount < 3;
      },
    },
    mutations: {
      retry: (failureCount, error: any) => {
        // Don't retry on JWT expiration errors
        if (isJwtExpiredError(error)) {
          handleJwtExpiration();
          return false;
        }
        return failureCount < 3;
      },
    },
  },
});

import { PublicClientApplication } from '@azure/msal-browser';

// Create MSAL instance for token refresh
export const msalInstance = new PublicClientApplication({
  auth: {
    clientId: import.meta.env.VITE_SSO_CLIENT_ID,
    authority: import.meta.env.VITE_SSO_AUTHORITY,
    redirectUri: window.location.origin + '/redirect.html',
  },
  cache: {
    cacheLocation: "localStorage"
  }
});

Amplify.configure(
  {
    Auth: {
      Cognito: {
        userPoolId: import.meta.env.VITE_USERPOOLID,
        userPoolClientId: import.meta.env.VITE_USERPOOLWEBCLIENTID,
      },
    },
    API: {
      GraphQL: {
        endpoint: `${import.meta.env.VITE_GRAPHQL_URL}/v1/graphql`,
        defaultAuthMode: "none",
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
  },
  {
    API: {
      GraphQL: {
        headers: async () => {
          // Check for SSO token first
          const ssoToken = localStorage.getItem('sso_token');
          if (ssoToken) {
            return { Authorization: `Bearer ${ssoToken}` };
          }
          // Fallback to Cognito
          const jwtToken = (
            await Auth.fetchAuthSession()
          ).tokens?.idToken?.toString();
          return { ...(jwtToken && { Authorization: `Bearer ${jwtToken}` }) };
        },
      },
      REST: {
        headers: async ({ apiName }) => {
          if (apiName === "auth") {
            // Check for SSO token first
            const ssoToken = localStorage.getItem('sso_token');
            if (ssoToken) {
              return { Authorization: `Bearer ${ssoToken}` } as any;
            }
            // Fallback to Cognito
            return {
              Authorization: `Bearer ${(await Auth.fetchAuthSession()).tokens?.idToken?.toString()}`,
            } as any;
          }
          return { "X-Api-Key": "1" } as any;
        },
        retryStrategy: { strategy: "no-retry" },
      },
    },
  }
);

cognitoUserPoolsTokenProvider.setKeyValueStorage(
  new CookieStorage({
    domain: location.hostname.endsWith("equalifyapp.com")
      ? "equalifyapp.com"
      : location.hostname,
    secure: false,
    sameSite: "lax",
  })
);

const router = createBrowserRouter([
  {
    path: "",
    element: <Navigation />,
    children: [
      { path: "", element: <Home /> },
      { path: "login", element: <Login /> },
      { path: "signup", element: <Signup /> },
      { path: "sso", element: <SsoCallback /> },
      { path: "audits", element: <Audits /> },
      { path: "audits/:auditId", element: <Audit /> },
      { path: "shared/:auditId", element: <Audit /> },
      { path: "audits/build", element: <BuildAudit /> },
      { path: "logs", element: <Logs /> },
      { path: "log/:logId", element: <Log /> },
      { path: "account", element: <Account /> },
      { path: "logout", element: <Logout /> },
    ],
    errorElement: (
      <>
        <Navigation />
        <Link
          to="/"
          className="absolute top-[calc(50%_-_100px)] left-[calc(50%_-_200px)] text-text text-center w-[400px] hover:opacity-50"
        >
          404 - Page not found!
        </Link>
      </>
    ),
  },
]);

import { MsalProvider } from '@azure/msal-react';

export const App = () => {
  const { announceMessage } = useGlobalStore();
  return (
  <MsalProvider instance={msalInstance}>
    <PostHogProvider
      apiKey={import.meta.env.VITE_POSTHOG_KEY}
      options={{ api_host: "https://us.posthog.com" }}
    >
      <div aria-live="assertive" role="status" className="sr-only">
        {announceMessage}
      </div>
      <QueryClientProvider client={queryClient}>
        <RouterProvider router={router} />
      </QueryClientProvider>
      <Toaster 
        theme="dark"
      />
    </PostHogProvider>
  </MsalProvider>
  );
};
