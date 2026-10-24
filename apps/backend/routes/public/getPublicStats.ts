import { db, getLiveKpis, getMonthlyKpis, getKpiSnapshot, getRedSitesTotal } from '#src/utils';
import type { LiveKpis, MonthlyKpi, KpiSnapshot, RedSitesTotal } from '#src/utils';

// Public, unauthenticated platform statistics for external dashboards (the
// Equalify Hub's About page, DASE Hub's Key Results page) and the monthly
// development report. Totals only — no names, emails, departments, or any
// per-user data.
//
//   GET /public/getPublicStats
//
// Every figure comes from utils/kpis.ts, the module Account > Statistics
// (getSystemStats) also reads, so the surfaces always agree. The flat top-level
// fields are kept for existing consumers; new consumers should read:
//   live      current figures (totals and rolling 30-day windows)
//   monthly   one row per UTC calendar month, oldest first; the last row is the
//             current, partial month (`partial: true`) - hubs that show a
//             monthly snapshot should use the last row with partial === false
//   snapshot  the daily Title II figures (Red adoption, conformance,
//             remediation), or null until the first daily run has happened
//   redSitesTotal  the Red adoption denominator and the date it was given
//
// Cached in memory for 5 minutes per container so an open route can't be used
// to hammer the database or CloudWatch.
const CACHE_TTL_MS = 5 * 60 * 1000;
// A year of full months plus the current one; CloudWatch retains 15 months
const MONTHS_OF_HISTORY = 13;

interface PublicStats {
    totalUsers: number;
    newUsers30d: number;
    activeUsers30d: number;
    unitsServed30d: number;
    auditOwners: number;
    totalAudits: number;
    generatedAt: string;
    live: LiveKpis;
    monthly: MonthlyKpi[];
    snapshot: KpiSnapshot | null;
    redSitesTotal: RedSitesTotal | null;
}

let cache: { at: number; body: PublicStats } | null = null;

export const getPublicStats = async (): Promise<PublicStats> => {
    if (cache && Date.now() - cache.at < CACHE_TTL_MS) return cache.body;

    await db.connect();
    try {
        const [live, monthly, snapshot, redSitesTotal] = await Promise.all([
            getLiveKpis(),
            getMonthlyKpis(MONTHS_OF_HISTORY),
            getKpiSnapshot(),
            getRedSitesTotal(),
        ]);
        const body: PublicStats = {
            totalUsers:     live.totalUsers,
            newUsers30d:    live.newUsers30d,
            activeUsers30d: live.activeUsers30d,
            unitsServed30d: live.unitsServed30d,
            auditOwners:    live.auditOwners,
            totalAudits:    live.totalAudits,
            generatedAt:    new Date().toISOString(),
            live,
            monthly,
            snapshot,
            redSitesTotal,
        };
        cache = { at: Date.now(), body };
        return body;
    } finally {
        await db.clean();
    }
};
