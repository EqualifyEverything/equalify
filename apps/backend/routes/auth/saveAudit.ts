import { db, event } from '#src/utils'

export const saveAudit = async () => {
    try {
        const { name, audit_url, is_sitemap, interval } = event.body;
        const scheduledAt = new Date();
        scheduledAt.setDate(scheduledAt.getDate() + 1);
        await db.connect();
        const id = (await db.query({
            text: `INSERT INTO "audits" 
                ("user_id", "organization_id", "name", "audit_url", "is_sitemap", "interval", "scheduled_at", "status") 
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING "id"`,
            values: [event.claims.sub, event.claims.profile, name, audit_url, is_sitemap, interval, scheduledAt, 'new'],
        })).rows[0].id;
        await db.clean();
        return { id }
    }
    catch (err) {
        return { message: err?.detail ?? err }
    }
}