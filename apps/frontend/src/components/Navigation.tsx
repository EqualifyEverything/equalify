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
import styles from "./Navigation.module.scss";
import { Logo } from "./Logo";
import * as AccessibleIcon from "@radix-ui/react-accessible-icon";
import { MdDarkMode, MdOutlineDarkMode } from "react-icons/md";
import { Footer } from "./Footer";

export const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { loading, authenticated, setAuthenticated, darkMode, setDarkMode } =
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
      document.body.classList.add("dark");
    } else {
      document.body.classList.remove("dark");
    }
  }, [darkMode]);

  useEffect(() => {
    const checkAuth = async () => {
      // Check for SSO session first
      const ssoToken = localStorage.getItem("sso_token");
      if (ssoToken) {
        // Parse the JWT to get the OID
        try {
          const payload = JSON.parse(atob(ssoToken.split(".")[1]));
          const userId = payload.oid || payload.sub;

          // Validate with backend before trusting the token
          if (userId) {
            try {
              const API = await import("aws-amplify/api");
              await API.get({
                apiName: "auth",
                path: "/getAccount",
              }).response;

              // Only set authenticated if backend validation succeeds
              setAuthenticated(userId);
              posthog?.identify(userId, { email: payload?.email });
            } catch (backendError: any) {
              // Backend rejected - token is invalid or user not authorized
              console.error(
                "Backend validation failed on page load:",
                backendError
              );
              localStorage.removeItem("sso_token");
              setAuthenticated(false);

              // Parse error message from response - AWS Amplify wraps errors differently
              let errorMessage = "You are not authorized to access Equalify.";

              // Check direct message property
              if (backendError?.message) {
                errorMessage = backendError.message;
              }
              // Check response body
              else if (backendError?.response?.body) {
                try {
                  const errorBody = backendError.response.body;
                  const parsed =
                    typeof errorBody === "string"
                      ? JSON.parse(errorBody)
                      : errorBody;
                  errorMessage = parsed?.message || errorMessage;
                } catch (e) {
                  // Keep default error message
                }
              }

              if (
                !location.pathname.startsWith("/login") &&
                !location.pathname.startsWith("/shared/")
              ) {
                navigate("/login?error=" + encodeURIComponent(errorMessage));
              }
              return;
            }
          }
        } catch (error) {
          console.error("Failed to parse SSO token:", error);
          localStorage.removeItem("sso_token");
          setAuthenticated(false);
        }

        if (location.pathname === "/") {
          navigate("/audits");
        }
        return;
      }

      // Check Cognito session
      const attributes = (await Auth.fetchAuthSession()).tokens?.idToken
        ?.payload;
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

  const isAuthRoute =
    location.pathname.startsWith("/login") ||
    location.pathname.startsWith("/signup");

  const navItems = !authenticated
    ? [
        { label: "Log In", value: "/login" },
        { label: "Sign Up", value: "/signup" },
      ]
    : [
        { label: "Audits", value: "/audits" },
        { label: "Logs", value: "/logs" },
        { label: "Account", value: "/account" },
      ];

  return (
    <>
    <div className="app-container" data-vaul-drawer-wrapper>
      <GlobalErrorHandler />
      <div className="container">
        {!isAuthRoute && (
          <div className={styles.navigation}>
            <Logo />
            <div className={styles.nav_menu}>
              {navItems.map((obj) => (
                <Link
                  key={obj.value}
                  to={obj.value}
                  className={
                    styles["link"] +
                    " " +
                    (location.pathname === obj.value ? styles["active"] : "")
                  }
                >
                  {obj.label}
                </Link>
              ))}
            </div>
            <div className={styles.nav_buttons}>
              {authenticated && user && (
                <div className={styles["logout-text"]}>
                  Signed in as <b>{user.name}</b>
                  <br />
                  <Link to="/logout">Logout</Link>
                </div>
              )}
              {/* <button onClick={() => setDarkMode(!darkMode)}>
                <AccessibleIcon.Root
                  label={`Switch to ${darkMode ? "Light Mode" : "Dark Mode"}`}
                >
                  {!darkMode ? <MdOutlineDarkMode /> : <MdDarkMode />}
                </AccessibleIcon.Root>
              </button> */}
            </div>
          </div>
        )}
        {loading && <Loader />}
        <Outlet />
      </div>
      {!isAuthRoute && <Footer />}
    </div>
    
    </>
  );
};
