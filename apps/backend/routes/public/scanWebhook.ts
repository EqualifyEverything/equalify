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

    // Pre-compute all blocker data (CPU-only, no DB calls)
    let processedBlockers = 0;
    let blockerErrors = 0;

    const prepared = [];
    const allTags = new Map();       // tagId -> tag content (deduped)
    const allMessages = new Map();   // messageId -> { content, category } (deduped)

    for (const blocker of blockers) {
        try {
            const contentNormalized = normalizeHtmlWithVdom(blocker.node);
            const contentHashId = hashStringToUuid(contentNormalized);

            const tagIds = blocker.tags.map((tag: string) => {
                const tagId = hashStringToUuid(tag);
                allTags.set(tagId, tag);
                return tagId;
            });

            const messageId = hashStringToUuid(blocker.description + blocker.test);
            allMessages.set(messageId, { content: blocker.description, category: blocker.test || 'unknown' });

            prepared.push({
                node: blocker.node,
                contentNormalized,
                contentHashId,
                shortId: generateShortId(),
                tagIds,
                messageId,
                isIgnored: ignoredBlockerHashes.includes(contentHashId.replaceAll('-', '')),
            });
        } catch (prepError: any) {
            blockerErrors++;
            console.error(`Error pre-processing blocker:`, prepError);
            await logScanError('blocker_processing_error', prepError?.message || 'Failed to pre-process blocker', {
                blockerTest: blocker.test,
                blockerDescription: blocker.description?.substring(0, 100)
            });
        }
    }

    // Bulk insert all blocker data (~6 queries instead of ~1300)
    if (prepared.length > 0) {
        try {
            // 1. Bulk insert blockers with short_id collision retry
            let blockerIds: string[];
            let insertAttempts = 0;
            while (true) {
                try {
                    const vals = [];
                    const params = [];
                    let p = 1;
                    for (const bd of prepared) {
                        vals.push(`($${p}, $${p+1}, $${p+2}, $${p+3}, $${p+4}, $${p+5}, $${p+6}, $${p+7})`);
                        params.push(auditId, JSON.stringify([]), bd.node, bd.contentNormalized, bd.contentHashId, bd.shortId, urlId, effectiveScanId);
                        p += 8;
                    }
                    const result = await db.query({
                        text: `INSERT INTO "blockers" ("audit_id", "targets", "content", "content_normalized", "content_hash_id", "short_id", "url_id", "scan_id") VALUES ${vals.join(', ')} RETURNING "id"`,
                        values: params,
                    });
                    blockerIds = result.rows.map((r: any) => r.id);
                    break;
                } catch (err) {
                    insertAttempts++;
                    if ((err as any)?.message?.includes('blockers_short_id') && insertAttempts < 3) {
                        for (const bd of prepared) bd.shortId = generateShortId();
                        continue;
                    }
                    throw err;
                }
            }

            // 2. Bulk insert ignored_blockers
            const ignoredEntries = prepared
                .map((bd, i) => ({ blockerId: blockerIds[i], isIgnored: bd.isIgnored }))
                .filter(e => e.isIgnored);
            if (ignoredEntries.length > 0) {
                const vals = [];
                const params = [];
                let p = 1;
                for (const entry of ignoredEntries) {
                    vals.push(`($${p}, $${p+1})`);
                    params.push(auditId, entry.blockerId);
                    p += 2;
                }
                await db.query({
                    text: `INSERT INTO "ignored_blockers" ("audit_id", "blocker_id") VALUES ${vals.join(', ')} ON CONFLICT DO NOTHING`,
                    values: params,
                });
            }

            // 3. Bulk insert tags (deduped across all blockers)
            if (allTags.size > 0) {
                const vals = [];
                const params = [];
                let p = 1;
                for (const [id, content] of allTags) {
                    vals.push(`($${p}, $${p+1})`);
                    params.push(id, content);
                    p += 2;
                }
                await db.query({
                    text: `INSERT INTO "tags" ("id", "content") VALUES ${vals.join(', ')} ON CONFLICT ("id") DO NOTHING`,
                    values: params,
                });
            }

            // 4. Bulk insert messages (deduped across all blockers)
            if (allMessages.size > 0) {
                const vals = [];
                const params = [];
                let p = 1;
                for (const [id, msg] of allMessages) {
                    vals.push(`($${p}, $${p+1}, $${p+2})`);
                    params.push(id, msg.content, msg.category);
                    p += 3;
                }
                await db.query({
                    text: `INSERT INTO "messages" ("id", "content", "category") VALUES ${vals.join(', ')} ON CONFLICT ("id") DO NOTHING`,
                    values: params,
                });
            }

            // 5. Bulk insert message_tags (deduped)
            const mtPairs = new Set();
            const mtVals = [];
            const mtParams = [];
            let mtp = 1;
            for (const bd of prepared) {
                for (const tagId of bd.tagIds) {
                    const key = `${bd.messageId}:${tagId}`;
                    if (!mtPairs.has(key)) {
                        mtPairs.add(key);
                        mtVals.push(`($${mtp}, $${mtp+1})`);
                        mtParams.push(bd.messageId, tagId);
                        mtp += 2;
                    }
                }
            }
            if (mtVals.length > 0) {
                await db.query({
                    text: `INSERT INTO "message_tags" ("message_id", "tag_id") VALUES ${mtVals.join(', ')} ON CONFLICT ("message_id", "tag_id") DO NOTHING`,
                    values: mtParams,
                });
            }

            // 6. Bulk insert blocker_messages
            {
                const vals = [];
                const params = [];
                let p = 1;
                for (let i = 0; i < prepared.length; i++) {
                    vals.push(`($${p}, $${p+1})`);
                    params.push(prepared[i].messageId, blockerIds[i]);
                    p += 2;
                }
                await db.query({
                    text: `INSERT INTO "blocker_messages" ("message_id", "blocker_id") VALUES ${vals.join(', ')} ON CONFLICT ("message_id", "blocker_id") DO NOTHING`,
                    values: params,
                });
            }

            processedBlockers = blockerIds.length;
        } catch (bulkError: any) {
            blockerErrors = prepared.length;
            console.error(`Error in bulk blocker insert:`, bulkError);
            await logScanError('blocker_processing_error', bulkError?.message || 'Failed to bulk insert blockers', {
                blockerCount: prepared.length,
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