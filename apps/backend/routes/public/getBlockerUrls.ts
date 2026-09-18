import { getBlockerUrls as getBlockerUrlsAuth } from "../auth"

export const getBlockerUrls = async () => {
    const response = await getBlockerUrlsAuth();
    return response;
}
