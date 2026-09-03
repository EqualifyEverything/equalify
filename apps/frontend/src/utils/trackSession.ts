import * as API from "aws-amplify/api";
import type { IPublicClientApplication } from "@azure/msal-browser";

const ORG_FIELDS = ["department", "companyName", "officeLocation", "jobTitle"] as const;
type OrgInfo = Partial<Record<(typeof ORG_FIELDS)[number], string>>;

const GRAPH_ME_URL =
  "https://graph.microsoft.com/v1.0/me?$select=department,companyName,officeLocation,jobTitle";

// Best effort: the SSO login already requests the User.Read scope, so we can
// read the signed-in user's directory record from Microsoft Graph and let the
// backend attribute the session to a campus unit ("units served" KPI).
// Any failure just yields {} — the session is still recorded.
const fetchGraphOrgInfo = async (msal: IPublicClientApplication): Promise<OrgInfo> => {
  try {
    const account = msal.getAllAccounts()[0];
    if (!account) return {};
    const { accessToken } = await msal.acquireTokenSilent({ scopes: ["User.Read"], account });
    const response = await fetch(GRAPH_ME_URL, { headers: { Authorization: `Bearer ${accessToken}` } });
    if (!response.ok) return {};
    const me = await response.json();
    const org: OrgInfo = {};
    for (const key of ORG_FIELDS) {
      if (typeof me?.[key] === "string" && me[key].trim()) org[key] = me[key].trim();
    }
    return org;
  } catch (err) {
    console.warn("Could not read org info from Microsoft Graph:", err);
    return {};
  }
};

// Records one session (app load or login) for the monthly KPIs on
// Account > Statistics. Call once per boot or login, after the backend has
// accepted the user's token. Never throws — tracking must not block the app.
export const trackSession = async (msal?: IPublicClientApplication) => {
  try {
    const isSso = !!localStorage.getItem("sso_token");
    const org = isSso && msal ? await fetchGraphOrgInfo(msal) : {};
    await API.post({
      apiName: "auth",
      path: "/trackSession",
      options: { body: { ...org, landing: location.pathname } },
    }).response;
  } catch (err) {
    console.warn("trackSession failed:", err);
  }
};
