import { db, event, hashStringToUuid, normalizeHtmlWithVdom, generateShortId } from "#src/utils"

export const scanWebhook = async () => {
    console.log(JSON.stringify(event));
    const { auditId, scanId, urlId, blockers, status, error } = event.body;
    await db.connect();

    const ignoredBlockerHashes = (await db.query({
        text: `SELECT b.content_hash_id FROM ignored_blockers as ib LEFT OUTER JOIN blockers as b ON ib.blocker_id = b.id WHERE ib.audit_id=$1`,
        values: [auditId],
    }))?.rows?.map(obj => obj.content_hash_id.replaceAll('-', ''));

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
        const shortId = generateShortId();

        // if (ignoredBlockerHashes.includes(contentHashId.replaceAll('-', ''))) {
        //     continue;
        // }

        // Insert blocker
        const blockerId = (await db.query({
            text: `
                INSERT INTO "blockers" ("audit_id", "targets", "content", "content_normalized", "content_hash_id", "short_id", "url_id", "scan_id") 
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
                RETURNING "id"
            `,
            values: [auditId, JSON.stringify([]), blocker.node, contentNormalized, contentHashId, shortId, urlId, scanId],
        })).rows[0].id;

        if (ignoredBlockerHashes.includes(contentHashId.replaceAll('-', ''))) {
            await db.query({
                text: `INSERT INTO "ignored_blockers" ("audit_id", "blocker_id") VALUES ($1, $2) ON CONFLICT DO NOTHING`,
                values: [auditId, blockerId],
            })
        }

        const tagIds = [];
        for (const tag of blocker.tags) {
            const tagId = hashStringToUuid(tag);
            tagIds.push(tagId);
            await db.query({
                text: `INSERT INTO "tags" ("id", "content") VALUES ($1, $2) ON CONFLICT ("id") DO NOTHING`,
                values: [tagId, tag],
            });
        }

        // Insert or get blocker type based on description
        const blockerTypeId = hashStringToUuid(blocker.description + blocker.test);
        await db.query({
            text: `
                    INSERT INTO "messages" ("id", "content", "category") 
                    VALUES ($1, $2, $3) 
                    ON CONFLICT ("id") DO NOTHING
                `,
            values: [blockerTypeId, blocker.description, blocker.test || 'unknown'],
        });

        // Link blocker type tags
        for (const tagId of tagIds) {
            await db.query({
                text: `
                    INSERT INTO "message_tags" ("message_id", "tag_id") 
                    VALUES ($1, $2)
                    ON CONFLICT ("message_id", "tag_id") DO NOTHING
                `,
                values: [blockerTypeId, tagId],
            });
        }

        // Link blocker type to blocker
        await db.query({
            text: `
                INSERT INTO "blocker_messages" ("message_id", "blocker_id") 
                VALUES ($1, $2)
                ON CONFLICT ("message_id", "blocker_id") DO NOTHING
            `,
            values: [blockerTypeId, blockerId],
        });
    }

    // Should we update the scan status? Let's do that.
    const totalPages = (await db.query({
        text: `SELECT "pages" FROM "scans" WHERE "id"=$1`,
        values: [scanId],
    })).rows[0].pages.length;
    const scannedPages = parseInt((await db.query({
        text: `SELECT COUNT(DISTINCT "url_id") FROM "blockers" WHERE "scan_id"=$1`,
        values: [scanId],
    })).rows[0].count);
    const percentage = Math.min(parseInt(((scannedPages / totalPages) * 100).toFixed(0)), 100);
    await db.query({
        text: `UPDATE "scans" SET "percentage"=$1 WHERE "id"=$2`,
        values: [percentage, scanId],
    })

    await db.clean();
    return;
} 