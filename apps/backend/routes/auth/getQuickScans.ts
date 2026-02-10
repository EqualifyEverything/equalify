import { db, event } from '#src/utils';

export const getQuickScans = async () => {
    try {
        await db.connect();

        const result = await db.query({
            text: `
                SELECT 
                    a.id,
                    a.name,
                    a.created_at,
                    a.status,
                    u.url,
                    u.type,
                    s.status AS scan_status,
                    s.percentage AS scan_percentage,
                    s.updated_at AS scan_updated_at,
                    (SELECT COUNT(*) FROM blockers b WHERE b.scan_id = s.id) AS blocker_count
                FROM audits a
                LEFT JOIN urls u ON u.audit_id = a.id
                LEFT JOIN LATERAL (
                    SELECT * FROM scans sc WHERE sc.audit_id = a.id ORDER BY sc.created_at DESC LIMIT 1
                ) s ON true
                WHERE a.user_id = $1 AND a.interval = 'Quick Scan'
                ORDER BY a.created_at DESC
            `,
            values: [event.claims.sub],
        });

        await db.clean();

        return {
            statusCode: 200,
            body: result.rows,
        };
    }
    catch (err) {
        return { message: err?.detail ?? err };
    }
}
