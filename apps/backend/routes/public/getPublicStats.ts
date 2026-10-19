import { db } from '#src/utils';

// Public, unauthenticated usage totals for external dashboards (DASE Hub's
// Key Results page). Totals only — no names, emails, departments, or any
// per-user data.
//
//   GET /public/getPublicStats
//
// Definitions match Account > Statistics (getSystemStats.ts) so the two
// surfaces always agree:
//   totalUsers     — accounts in Equalify
//   newUsers30d    — accounts created in the last 30 days
//   activeUsers30d — distinct accounts with a session (sign-in / app load) in the last 30 days
//   unitsServed30d — distinct SSO departments with a session in the last 30 days
//   auditOwners    — distinct accounts that have created at least one audit
//   totalAudits    — audits created
//
// Cached in memory for 5 minutes per container so an open route can't be
// used to hammer the database.

const CACHE_TTL_MS = 5 * 60 * 1000;

interface PublicStats {
    totalUsers: number;
    newUsers30d: number;
    activeUsers30d: number;
    unitsServed30d: number;
    auditOwners: number;
    totalAudits: number;
    generatedAt: string;
}

let cache: { at: number; body: PublicStats } | null = null;

export const getPublicStats = async (): Promise<PublicStats> => {
    if (cache && Date.now() - cache.at < CACHE_TTL_MS) return cache.body;

    await db.connect();
    try {
        const row = (await db.query({
            text: `
                SELECT
                    (SELECT COUNT(*) FROM users)                                                              AS total_users,
                    (SELECT COUNT(*) FROM users    WHERE created_at >= NOW() - INTERVAL '30 days')           AS new_users_30d,
                    (SELECT COUNT(DISTINCT user_id)    FROM sessions WHERE created_at >= NOW() - INTERVAL '30 days') AS active_users_30d,
                    (SELECT COUNT(DISTINCT department) FROM sessions WHERE created_at >= NOW() - INTERVAL '30 days'
                                                                      AND department IS NOT NULL)            AS units_served_30d,
                    (SELECT COUNT(DISTINCT user_id) FROM audits)                                              AS audit_owners,
                    (SELECT COUNT(*) FROM audits)                                                             AS total_audits
            `,
        })).rows[0];

        const body: PublicStats = {
            totalUsers:     parseInt(row.total_users),
            newUsers30d:    parseInt(row.new_users_30d),
            activeUsers30d: parseInt(row.active_users_30d),
            unitsServed30d: parseInt(row.units_served_30d),
            auditOwners:    parseInt(row.audit_owners),
            totalAudits:    parseInt(row.total_audits),
            generatedAt:    new Date().toISOString(),
        };
        cache = { at: Date.now(), body };
        return body;
    } finally {
        await db.clean();
    }
};
