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
                    COALESCE(s.blocker_count, 0) AS blocker_count
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
            body: JSON.stringify(result.rows),
        };
    }
    catch (err) {
        // An explicit statusCode opts this response out of API Gateway's
        // "simple response" format (which treats a plain returned object as
        // an implicit 200 JSON body) and into the strict Lambda-proxy
        // contract, which requires `body` to already be a JSON string —
        // without that, API Gateway rejects the response with its own
        // opaque 500, masking whatever error actually happened. Logging
        // here too: this catch previously returned silently, leaving no
        // trace of the real error in CloudWatch.
        console.error('getQuickScans error:', err);
        return {
            statusCode: 500,
            body: JSON.stringify({ message: err?.detail ?? err?.message ?? String(err) }),
        };
    }
}
