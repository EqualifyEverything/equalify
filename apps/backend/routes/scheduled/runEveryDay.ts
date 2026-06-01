import { db } from "#src/utils";

//
// Daily maintenance: move blockers from non-latest scans into the
// stale_blockers graveyard. Keeps the active blockers table small.
//
// Wire this to an EventBridge rule firing once per day (off-peak ideally).
//

export const runEveryDay = async () => {
    await db.connect();
    const t0 = Date.now();

    const SCANS_PER_BATCH = 10;
    const SOFT_DEADLINE_MS = 100000; // stop starting new batches after ~100s

    let totalMoved = 0;
    let batches = 0;
    let bmMoved = 0;
    let bmBatches = 0;

    try {
        while (Date.now() - t0 < SOFT_DEADLINE_MS) {
            const moveResult = await db.query({
                text: `
                    WITH latest AS (
                        SELECT DISTINCT ON (audit_id) id
                        FROM scans
                        ORDER BY audit_id, created_at DESC
                    ),
                    target_scans AS (
                        SELECT DISTINCT b.scan_id
                        FROM blockers b
                        WHERE b.scan_id IS NOT NULL
                        AND b.scan_id NOT IN (SELECT id FROM latest)
                        LIMIT ${SCANS_PER_BATCH}
                    ),
                    moved AS (
                        DELETE FROM blockers
                        WHERE scan_id IN (SELECT scan_id FROM target_scans)
                        RETURNING id, created_at, updated_at, audit_id, content, content_normalized,
                                  content_hash_id, targets, equalified, url_id, scan_id, short_id
                    )
                    INSERT INTO stale_blockers
                        (id, created_at, updated_at, audit_id, content, content_normalized,
                         content_hash_id, targets, equalified, url_id, scan_id, short_id)
                    SELECT id, created_at, updated_at, audit_id, content, content_normalized,
                           content_hash_id, targets, equalified, url_id, scan_id, short_id
                    FROM moved
                `,
            });

            const rowsMoved = moveResult.rowCount ?? 0;
            batches++;
            totalMoved += rowsMoved;

            if (rowsMoved === 0) break; // nothing left to move
        }

        // Also sweep any orphan blocker_messages whose blocker now lives in stale_blockers.
        // Bounded so a backlog can't blow the daily run.
        const ORPHAN_BM_BATCH_ROWS = 200000;
        const ORPHAN_BM_MAX_BATCHES = 10;
        while (bmBatches < ORPHAN_BM_MAX_BATCHES && Date.now() - t0 < SOFT_DEADLINE_MS) {
            const result = await db.query({
                text: `
                    WITH targets AS (
                        SELECT bm.id FROM blocker_messages bm
                        WHERE NOT EXISTS (SELECT 1 FROM blockers b WHERE b.id = bm.blocker_id)
                        LIMIT ${ORPHAN_BM_BATCH_ROWS}
                    ),
                    moved AS (
                        DELETE FROM blocker_messages
                        WHERE id IN (SELECT id FROM targets)
                        RETURNING id, created_at, updated_at, message_id, blocker_id
                    )
                    INSERT INTO stale_blocker_messages
                        (id, created_at, updated_at, message_id, blocker_id)
                    SELECT id, created_at, updated_at, message_id, blocker_id FROM moved
                `,
            });
            const moved = result.rowCount ?? 0;
            bmBatches++;
            bmMoved += moved;
            if (moved === 0) break;
        }
        console.log(`runEveryDay: also moved ${bmMoved} orphan blocker_messages in ${bmBatches} batch(es)`);
    } catch (err: any) {
        console.error("runEveryDay error:", err);
        try { await db.clean(); } catch {}
        throw err;
    }

    await db.clean();
    console.log(`runEveryDay: moved ${totalMoved} stale blockers in ${batches} batch(es), ${bmMoved} orphan blocker_messages in ${bmBatches} batch(es) (${Date.now() - t0}ms)`);
    return { totalMoved, batches, bmMoved, bmBatches, ms: Date.now() - t0 };
};
