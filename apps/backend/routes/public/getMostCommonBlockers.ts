import { getMostCommonBlockers as getMostCommonBlockersAuth } from "../auth"

export const getMostCommonBlockers = async () => {
    const response = await getMostCommonBlockersAuth();
    return response;
}
