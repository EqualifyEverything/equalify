import { useGlobalStore } from "#src/utils";
import { useEffect } from "react";
import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { Loader, GlobalErrorHandler } from ".";
import * as Auth from "aws-amplify/auth";
import { usePostHog } from "posthog-js/react";
import { useUser } from "../queries";
import * as Avatar from "@radix-ui/react-avatar";
import generateAbbreviation from "#src/utils/generateAbbreviation.ts";
import * as DropdownMenu from "@radix-ui/react-dropdown-menu";
import { useMsalTokenRefresh } from "../hooks";

export const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { loading, authenticated, setAuthenticated, darkMode } =
    useGlobalStore();
  const posthog = usePostHog();
  const { data: user } = useUser();
  
  // Handle MSAL token refresh
  useMsalTokenRefresh();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [location]);

  useEffect(() => {
    if (darkMode) {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  }, [darkMode]);

  useEffect(() => {
    const checkAuth = async () => {
      // Check for SSO session first
      const ssoToken = localStorage.getItem('sso_token');
      if (ssoToken) {
        // Parse the JWT to get the OID
        try {
          const payload = JSON.parse(atob(ssoToken.split('.')[1]));
          const userId = payload.oid || payload.sub;
          
          // Validate with backend before trusting the token
          if (userId) {
            try {
              const API = await import('aws-amplify/api');
              await API.get({
                apiName: 'auth',
                path: '/getAccount',
              }).response;
              
              // Only set authenticated if backend validation succeeds
              setAuthenticated(userId);
              posthog?.identify(userId, { email: payload?.email });
            } catch (backendError: any) {
              // Backend rejected - token is invalid or user not authorized
              console.error('Backend validation failed on page load:', backendError);
              localStorage.removeItem('sso_token');
              setAuthenticated(false);
              
              // Parse error message from response - AWS Amplify wraps errors differently
              let errorMessage = 'You are not authorized to access Equalify.';
              
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
              
              if (!location.pathname.startsWith('/login') && !location.pathname.startsWith('/shared/')) {
                navigate('/login?error=' + encodeURIComponent(errorMessage));
              }
              return;
            }
          }
        } catch (error) {
          console.error('Failed to parse SSO token:', error);
          localStorage.removeItem('sso_token');
          setAuthenticated(false);
        }
        
        if (location.pathname === "/") {
          navigate("/audits");
        }
        return;
      }
      
      // Check Cognito session
      const attributes = (await Auth.fetchAuthSession()).tokens?.idToken?.payload;
      if (!attributes) {
        setAuthenticated(false);
        if (!location.pathname.startsWith("/shared/")) {
          navigate(`/${location.search}`);
        }
      } else {
        setAuthenticated(attributes?.sub as unknown as boolean);
        posthog?.identify(attributes?.sub, { email: attributes?.email });
        if (location.pathname === "/") {
          navigate("/audits");
        }
      }
    };
    
    checkAuth();
  }, []);

  // on location change, focus to the first element with class 'initial-focus-element'
  useEffect(() => {
    const focusEl = document.getElementsByClassName(
      "initial-focus-element"
    )[0] as HTMLElement;
    const tabIndex = focusEl?.getAttribute("tabindex");
    focusEl?.setAttribute("tabindex", tabIndex ?? "-1");
    focusEl?.focus();
  }, [location]);

  return (
    <div>
      <GlobalErrorHandler />
      <div className="p-4">
        <div className="flex flex-col sm:flex-row items-center justify-start mb-4 gap-4">
          <Link to="/" className="relative hover:opacity-50">
            <img className="w-[150px]" src="/logo.svg" />
          </Link>
          <div className="flex flex-row items-center gap-4">
            {(!authenticated
              ? [
                  { label: "Log In", value: "/login" },
                  { label: "Sign Up", value: "/signup" },
                ]
              : [
                  { label: "Audits", value: "/audits" },
                  { label: "Logs", value: "/logs" },
                  { label: "Account", value: "/account" },
                ]
            ).map((obj) => (
              <Link
                key={obj.value}
                to={obj.value}
                className={`hover:opacity-50 ${location.pathname === obj.value && "font-bold"}`}
              >
                {obj.label}
              </Link>
            ))}
            {authenticated && user && (
              <DropdownMenu.Root>
                <DropdownMenu.Trigger className="bg-teal-800 text-white uppercase p-3 border-0 rounded-full w-10 h-10">
                  <Avatar.Root>
                    <Avatar.Fallback>
                      {generateAbbreviation(user.name)}
                    </Avatar.Fallback>
                  </Avatar.Root>
                </DropdownMenu.Trigger>
                <DropdownMenu.Content>
                  <DropdownMenu.Item><Link to='/logout'>Logout</Link></DropdownMenu.Item>
                </DropdownMenu.Content>
              </DropdownMenu.Root>
            )}
          </div>
        </div>
        {loading && <Loader />}
        <Outlet />
      </div>
      <div className="p-4 flex flex-col sm:flex-row items-center justify-between">
        <div>© {new Date().getFullYear()} Equalify. All rights reserved</div>
        {/* <button onClick={() => setDarkMode(!darkMode)}>{`Switch to ${darkMode ? 'Light Mode' : 'Dark Mode'}`}</button> */}
      </div>
    </div>
  );
};
