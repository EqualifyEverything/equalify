import { getMostCommonUrls as getMostCommonUrlsAuth } from "../auth"

export const getMostCommonUrls = async () => {
    const response = await getMostCommonUrlsAuth();
    return response;
}
