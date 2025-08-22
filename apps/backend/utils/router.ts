import { event } from "./event";

export const router = async (routes) => {
    for (const [routeName, routeFunction] of Object.entries(routes)) {
        if (event.path.endsWith(`/${routeName}`)) {
            return routeFunction();
        }
    }
};