import { getBlockerSummary as getBlockerSummaryAuth } from "../auth";

export const getBlockerSummary = async () => {
    return getBlockerSummaryAuth();
};
