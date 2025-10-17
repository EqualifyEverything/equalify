import { db, event, hashStringToUuid, normalizeHtmlWithVdom } from "#src/utils"

export const scanWebhook = async () => {
    console.log(JSON.stringify(event));
    const { auditId, scanId, urlId, blockers, status, error } = event.body;
    await db.connect();

    // Handle failed scans
    if (status === 'failed') {
        await db.query({
            text: `UPDATE "audits" SET "status"=$1, "response"=$2 WHERE "id"=$3`,
            values: ['failed', JSON.stringify({ error, urlId }), auditId],
        });
        await db.clean();
        return { success: true, message: 'Scan failure recorded' };
    }

    // Store the response in audits
    await db.query({
        text: `UPDATE "audits" SET "status"=$1, "response"=$2 WHERE "id"=$3`,
        values: ['complete', JSON.stringify(event.body), auditId],
    });

    // Premature exit
    // await db.clean();
    // return;

    for (const blocker of blockers) {
        const contentNormalized = normalizeHtmlWithVdom(blocker.node);
        const contentHashId = hashStringToUuid(contentNormalized);

        // Insert or update blocker, setting equalified=false
        const blockerResult = await db.query({
            text: `
                INSERT INTO "blockers" ("audit_id", "targets", "content", "content_normalized", "content_hash_id", "url_id", "equalified") 
                VALUES ($1, $2, $3, $4, $5, $6, $7)
                ON CONFLICT ("content_hash_id", "url_id") 
                DO UPDATE SET "equalified" = false
                RETURNING "id"
            `,
            values: [auditId, JSON.stringify([]), blocker.node, contentNormalized, contentHashId, urlId, false],
        });
        const blockerId = blockerResult.rows[0].id;

        // Insert or update blocker_update for today
        await db.query({
            text: `
                INSERT INTO "blocker_updates" ("audit_id", "blocker_id", "equalified", "scan_id") 
                VALUES ($1, $2, $3, $4)
                ON CONFLICT ("blocker_id", "created_at")
                DO UPDATE SET "equalified" = false
            `,
            values: [auditId, blockerId, false, scanId],
        });

        const tagIds = [];
        for (const tag of blocker.tags) {
            const tagId = hashStringToUuid(tag);
            tagIds.push(tagId);
            await db.query({
                text: `INSERT INTO "blocker_tags" ("id", "tag") VALUES ($1, $2) ON CONFLICT ("id") DO NOTHING`,
                values: [tagId, tag],
            });
        }

        // Insert or get blocker type based on description
        const blockerTypeId = hashStringToUuid(blocker.description);
        await db.query({
            text: `
                    INSERT INTO "blocker_types" ("id", "message", "type") 
                    VALUES ($1, $2, $3) 
                    ON CONFLICT ("id") DO NOTHING
                `,
            values: [blockerTypeId, blocker.description, blocker.type || 'unknown'],
        });

        // Link blocker type tags
        for (const tagId of tagIds) {
            await db.query({
                text: `
                    INSERT INTO "blocker_type_tags" ("blocker_type_id", "blocker_tag_id") 
                    VALUES ($1, $2)
                    ON CONFLICT ("blocker_type_id", "blocker_tag_id") DO NOTHING
                `,
                values: [blockerTypeId, tagId],
            });
        }

        // Link blocker type to blocker
        await db.query({
            text: `
                INSERT INTO "blocker_type_blockers" ("blocker_type_id", "blocker_id") 
                VALUES ($1, $2)
                ON CONFLICT ("blocker_type_id", "blocker_id") DO NOTHING
            `,
            values: [blockerTypeId, blockerId],
        });
    }

    await db.clean();
    return;
} 