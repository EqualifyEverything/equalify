import { db, event, getLiveKpis, getMonthlyKpis, getKpiSnapshot, getRedSitesTotal } from "#src/utils";

// Admin-only platform statistics for Account > Statistics. Every figure comes
// from utils/kpis.ts, the same module getPublicStats reads, so the admin panel,
// the public hubs and the monthly report can't drift apart.
//
// Months of month-over-month KPI history returned alongside the live figures
// (the current, partial month plus the full months before it). CloudWatch keeps
// hourly-resolution datapoints for 15 months, so anything up to 12 is safe.
const MONTHS_OF_HISTORY = 6;

export const getSystemStats = async () => {
    await db.connect();

    const userRow = (await db.query({
        text: `SELECT type FROM users WHERE id = $1`,
        values: [event.claims.sub],
    })).rows[0];

    if (userRow?.type !== "admin") {
        await db.clean();
        return { statusCode: 403, body: JSON.stringify({ error: "Forbidden" }) };
    }

    const [live, monthly, snapshot, redSitesTotal] = await Promise.all([
        getLiveKpis(),
        getMonthlyKpis(MONTHS_OF_HISTORY),
        getKpiSnapshot(),
        getRedSitesTotal(),
    ]);

    await db.clean();

    // Live figures stay at the top level (the panel has always read them there)
    return { ...live, monthly, snapshot, redSitesTotal };
};
