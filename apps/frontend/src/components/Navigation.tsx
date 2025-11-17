import { useGlobalStore } from "#src/utils";
import { useEffect } from "react";
import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { Loader } from ".";
import * as Auth from "aws-amplify/auth";
import { usePostHog } from "posthog-js/react";
import { useUser } from "../queries";
import * as Avatar from "@radix-ui/react-avatar";
import generateAbbreviation from "#src/utils/generateAbbreviation.ts";
import * as DropdownMenu from "@radix-ui/react-dropdown-menu";

export const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { loading, authenticated, setAuthenticated, darkMode, setDarkMode } =
    useGlobalStore();
  const posthog = usePostHog();
  const { data: user } = useUser();

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

  const authRoutes = ["/login", "/signup", "/shared"];

  useEffect(() => {
    const async = async () => {
      // Check for SSO session first
      const ssoToken = localStorage.getItem('sso_token');
      if (ssoToken) {
        // SSO is logged in, keep authenticated
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
        await Auth.fetchAuthSession({ forceRefresh: true });
        if (location.pathname === "/") {
          navigate("/audits");
        }
      }
    };
    async();
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
