import { db, event, hashStringToUuid, normalizeHtmlWithVdom, generateShortId } from "#src/utils"

export const scanWebhook = async () => {
    console.log(JSON.stringify(event));
    const { auditId, scanId, urlId, url, blockers, status, error } = event.body;
    
    // Validate required fields
    if (!auditId || !urlId) {
        console.error("Missing required fields: auditId or urlId", { auditId, urlId });
        return { success: false, message: 'Missing required fields: auditId and urlId are required' };
    }
    
    if (!scanId) {
        console.error("Missing scanId in webhook payload - looking up from audit", { auditId, urlId });
    }
    
    await db.connect();
    
    // If scanId is missing, look it up from the most recent scan for this audit
    let effectiveScanId = scanId;
    if (!effectiveScanId) {
        const scanResult = await db.query({
            text: `SELECT id FROM scans WHERE audit_id = $1 ORDER BY created_at DESC LIMIT 1`,
            values: [auditId],
        });
        if (scanResult?.rows?.[0]?.id) {
            effectiveScanId = scanResult.rows[0].id;
            console.log(`Resolved scanId from audit: ${effectiveScanId}`);
        } else {
            console.error("Could not resolve scanId for audit", { auditId });
            // Continue without scanId - we can still save blockers
        }
    }

    const ignoredBlockerHashes = (await db.query({
        text: `SELECT b.content_hash_id FROM ignored_blockers as ib LEFT OUTER JOIN blockers as b ON ib.blocker_id = b.id WHERE ib.audit_id=$1`,
        values: [auditId],
    }))?.rows?.map(obj => obj.content_hash_id.replaceAll('-', ''));

    // Helper function to log errors to the scans table (atomic append)
    const logScanError = async (errorType: string, errorMessage: string, errorDetails?: object) => {
        const errorEntry = {
            type: errorType,
            message: errorMessage,
            urlId,
            url: url || null,
            timestamp: new Date().toISOString(),
            ...(errorDetails && { details: errorDetails })
        };

        // Atomic append using PostgreSQL's jsonb_concat (||) operator
        if (effectiveScanId) {
            await db.query({
                text: `UPDATE "scans" SET "errors" = COALESCE("errors", '[]'::jsonb) || $1::jsonb WHERE "id"=$2`,
                values: [JSON.stringify([errorEntry]), effectiveScanId],
            });
        }

        console.log(`Scan error logged: ${errorType} - ${errorMessage}`);
    };

    // Helper function to update scan progress and status (atomic operation)
    const updateScanProgress = async () => {
        // Skip if no scanId available
        if (!effectiveScanId) {
            console.warn("Cannot update scan progress: no scanId available");
            return { percentage: 0, isComplete: false, scannedCount: 0, totalPages: 0 };
        }
        
        // Use a single atomic UPDATE with RETURNING to avoid race conditions
        // This atomically adds urlId to processed_pages if not present, then calculates progress
        const result = (await db.query({
            text: `
                UPDATE "scans" 
                SET 
                    "processed_pages" = CASE 
                        WHEN NOT (COALESCE("processed_pages", '[]'::jsonb) @> $1::jsonb)
                        THEN COALESCE("processed_pages", '[]'::jsonb) || $1::jsonb
                        ELSE "processed_pages"
                    END
                WHERE "id" = $2
                RETURNING 
                    "pages",
                    "processed_pages",
                    jsonb_array_length(COALESCE("processed_pages", '[]'::jsonb)) as scanned_count
            `,
            values: [JSON.stringify([urlId]), effectiveScanId],
        })).rows[0];

        if (!result) {
            console.error("Scan not found for id:", effectiveScanId);
            return { percentage: 0, isComplete: false, scannedCount: 0, totalPages: 0 };
        }

        const totalPages = result.pages?.length || 0;
        const scannedCount = result.scanned_count || 0;
        const percentage = totalPages > 0 ? Math.min(Math.round((scannedCount / totalPages) * 100), 100) : 0;
        const isComplete = scannedCount >= totalPages;

        // Second atomic update for percentage and status
        await db.query({
            text: `UPDATE "scans" SET "percentage"=$1, "status"=$2 WHERE "id"=$3`,
            values: [percentage, isComplete ? 'complete' : 'processing', effectiveScanId],
        });

        return { percentage, isComplete, scannedCount, totalPages };
    };

    // Handle failed scans
    if (status === 'failed') {
        // Determine the error type based on the error message
        let errorType = 'scan_failed';
        if (error?.includes('TimeoutError') || error?.includes('timeout') || error?.includes('Timeout')) {
            errorType = 'page_timeout';
        } else if (error?.includes('net::ERR_')) {
            errorType = 'network_error';
        } else if (error?.includes('failed to produce results')) {
            errorType = 'no_results';
        }

        await logScanError(errorType, error || 'Unknown scan failure');

        // Still count this page as processed (it was attempted)
        const { isComplete } = await updateScanProgress();

        // Only mark audit as failed if this is the only/last page, otherwise let other pages continue
        if (isComplete) {
            // Check if there were any successful pages by looking at blockers
            const hasSuccessfulPages = effectiveScanId ? (await db.query({
                text: `SELECT COUNT(*) FROM "blockers" WHERE "scan_id"=$1`,
                values: [effectiveScanId],
            })).rows[0].count > 0 : false;

            await db.query({
                text: `UPDATE "audits" SET "status"=$1, "response"=$2 WHERE "id"=$3`,
                values: [hasSuccessfulPages ? 'complete' : 'failed', JSON.stringify({ error, urlId }), auditId],
            });
        }

        await db.clean();
        return { success: true, message: 'Scan failure recorded' };
    }

    // Store the latest response payload and mark audit as processing (will be set to 'complete' or 'failed' when scan finishes)
    await db.query({
        text: `UPDATE "audits" SET "status"='processing', "response"=$1 WHERE "id"=$2`,
        values: [JSON.stringify(event.body), auditId],
    });

    // Premature exit
    // await db.clean();
    // return;

    // Process blockers with error handling
    let processedBlockers = 0;
    let blockerErrors = 0;

    for (const blocker of blockers) {
        try {
            const contentNormalized = normalizeHtmlWithVdom(blocker.node);
            const contentHashId = hashStringToUuid(contentNormalized);

            // if (ignoredBlockerHashes.includes(contentHashId.replaceAll('-', ''))) {
            //     continue;
            // }

            // Insert blocker with short_id collision retry
            let blockerId: string;
            let insertAttempts = 0;
            const MAX_SHORT_ID_ATTEMPTS = 3;
            while (true) {
                try {
                    const shortId = generateShortId();
                    blockerId = (await db.query({
                        text: `
                            INSERT INTO "blockers" ("audit_id", "targets", "content", "content_normalized", "content_hash_id", "short_id", "url_id", "scan_id")
                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
                            RETURNING "id"
                        `,
                        values: [auditId, JSON.stringify([]), blocker.node, contentNormalized, contentHashId, shortId, urlId, effectiveScanId],
                    })).rows[0].id;
                    break;
                } catch (insertError) {
                    insertAttempts++;
                    if (insertError?.message?.includes('blockers_short_id') && insertAttempts < MAX_SHORT_ID_ATTEMPTS) {
                        continue; // Retry with a new short_id
                    }
                    throw insertError; // Re-throw non-collision errors or if retries exhausted
                }
            }

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

            processedBlockers++;
        } catch (blockerError) {
            blockerErrors++;
            console.error(`Error processing blocker:`, blockerError);
            await logScanError('blocker_processing_error', blockerError?.message || 'Failed to process blocker', {
                blockerTest: blocker.test,
                blockerDescription: blocker.description?.substring(0, 100)
            });
        }
    }

    // Log a warning if some blockers failed to process
    if (blockerErrors > 0) {
        console.warn(`Processed ${processedBlockers}/${blockers.length} blockers. ${blockerErrors} failed.`);
    }

    // Update scan progress and status
    const { isComplete, percentage, scannedCount, totalPages } = await updateScanProgress();
    console.log(`Scan progress: ${scannedCount}/${totalPages} pages (${percentage}%) - ${isComplete ? 'COMPLETE' : 'processing'}`);

    // Update audit status when scan is complete
    if (isComplete) {
        await db.query({
            text: `UPDATE "audits" SET "status"=$1 WHERE "id"=$2`,
            values: ['complete', auditId],
        });
    }

    await db.clean();
    return;
} 