import { db, event } from "#src/utils";

//
// One-time migration to denormalize counts and move historical blockers
// to the stale_blockers graveyard table.
//
// POST /internal/migrateStaleBlockers
// body: {
//   phase: "ignored_hashes" | "scan_counts" | "move_stale",
//   scansPerBatch?: number,        // for move_stale, default 5
//   maxBatches?: number,            // for move_stale, default 20 (caps work per invocation)
//   auditId?: string,               // optional scope to one audit (testing)
//   dryRun?: boolean
// }
//
// Run phases in order: ignored_hashes → scan_counts → move_stale (repeat until done).
//

export const migrateStaleBlockers = async () => {
    const body = (event.body as any) || {};
    const phase: string = body.phase;
    const auditId: string | undefined = body.auditId;
    const dryRun: boolean = !!body.dryRun;

    if (!phase) {
        return {
            statusCode: 400,
            body: { error: "phase is required: 'ignored_hashes' | 'scan_counts' | 'move_stale'" },
        };
    }

    await db.connect();
    const t0 = Date.now();

    try {
        if (phase === "ignored_hashes") {
            // Backfill content_hash_id on existing ignored_blockers rows by joining
            // back to blockers. Safe to re-run (NULL-only update).
            const auditFilter = auditId ? `AND ib.audit_id = $1` : "";
            const values = auditId ? [auditId] : [];

            const result = await db.query({
                text: `
                    ${dryRun ? "SELECT COUNT(*) AS updated FROM" : "UPDATE"} ignored_blockers ib
                    ${dryRun ? "JOIN" : "SET content_hash_id = b.content_hash_id FROM"} blockers b
                    ${dryRun ? "ON" : "WHERE"} ib.blocker_id = b.id
                    AND ib.content_hash_id IS NULL
                    ${auditFilter}
                `,
                values,
            });

            const updated = dryRun ? result.rows[0]?.updated : result.rowCount;
            await db.clean();
            return {
                statusCode: 200,
                body: { phase, dryRun, updated, ms: Date.now() - t0 },
            };
        }

        if (phase === "scan_counts") {
            // Backfill blocker_count + equalified_count on every scan row from the
            // active blockers table. MUST run BEFORE any blockers are moved to stale.
            const auditFilter = auditId ? `WHERE s.audit_id = $1` : "";
            const values = auditId ? [auditId] : [];

            if (dryRun) {
                const result = await db.query({
                    text: `SELECT COUNT(*) AS scan_count FROM scans s ${auditFilter}`,
                    values,
                });
                await db.clean();
                return {
                    statusCode: 200,
                    body: { phase, dryRun, scansToUpdate: result.rows[0]?.scan_count, ms: Date.now() - t0 },
                };
            }

            const result = await db.query({
                text: `
                    UPDATE scans s SET
                        blocker_count = COALESCE((SELECT COUNT(*) FROM blockers WHERE scan_id = s.id), 0),
                        equalified_count = COALESCE((SELECT COUNT(*) FROM blockers WHERE scan_id = s.id AND equalified = true), 0)
                    ${auditFilter}
                `,
                values,
            });

            await db.clean();
            return {
                statusCode: 200,
                body: { phase, scansUpdated: result.rowCount, ms: Date.now() - t0 },
            };
        }

        if (phase === "move_stale") {
            // Move blockers for non-latest scans (per audit) from blockers → stale_blockers.
            // Batched: process N stale scans per Lambda invocation. Caller re-invokes until done.
            const scansPerBatch: number = body.scansPerBatch ?? 5;
            const maxBatches: number = body.maxBatches ?? 20;
            const softDeadlineMs = 100000; // stop starting new batches after ~100s (Lambda timeout buffer)

            // Pre-check: how many stale scans remain to migrate (have blockers still in active table)
            const remainingResult = await db.query({
                text: `
                    WITH latest AS (
                        SELECT DISTINCT ON (audit_id) id
                        FROM scans
                        ${auditId ? "WHERE audit_id = $1" : ""}
                        ORDER BY audit_id, created_at DESC
                    )
                    SELECT COUNT(DISTINCT b.scan_id) AS remaining
                    FROM blockers b
                    WHERE b.scan_id IS NOT NULL
                    AND b.scan_id NOT IN (SELECT id FROM latest)
                    ${auditId ? "AND b.audit_id = $1" : ""}
                `,
                values: auditId ? [auditId] : [],
            });
            const remainingStaleScans = parseInt(remainingResult.rows[0]?.remaining ?? "0", 10);

            if (dryRun) {
                await db.clean();
                return {
                    statusCode: 200,
                    body: { phase, dryRun, remainingStaleScans, ms: Date.now() - t0 },
                };
            }

            let batchesRun = 0;
            let totalRowsMoved = 0;
            const batchResults: any[] = [];

            while (batchesRun < maxBatches && Date.now() - t0 < softDeadlineMs) {
                const moveResult = await db.query({
                    text: `
                        WITH latest AS (
                            SELECT DISTINCT ON (audit_id) id
                            FROM scans
                            ${auditId ? "WHERE audit_id = $1" : ""}
                            ORDER BY audit_id, created_at DESC
                        ),
                        target_scans AS (
                            SELECT DISTINCT b.scan_id
                            FROM blockers b
                            WHERE b.scan_id IS NOT NULL
                            AND b.scan_id NOT IN (SELECT id FROM latest)
                            ${auditId ? "AND b.audit_id = $1" : ""}
                            LIMIT ${scansPerBatch}
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
                    values: auditId ? [auditId] : [],
                });

                const rowsMoved = moveResult.rowCount ?? 0;
                batchesRun++;
                totalRowsMoved += rowsMoved;
                batchResults.push({ batch: batchesRun, rowsMoved });

                if (rowsMoved === 0) break; // nothing left
            }

            await db.clean();
            return {
                statusCode: 200,
                body: {
                    phase,
                    batchesRun,
                    totalRowsMoved,
                    remainingStaleScansBefore: remainingStaleScans,
                    ms: Date.now() - t0,
                    stoppedReason: batchesRun >= maxBatches
                        ? "max_batches_reached"
                        : Date.now() - t0 >= softDeadlineMs
                            ? "soft_deadline"
                            : "no_more_work",
                    batches: batchResults,
                },
            };
        }

        await db.clean();
        return {
            statusCode: 400,
            body: { error: `unknown phase '${phase}'` },
        };
    } catch (err: any) {
        try { await db.clean(); } catch {}
        console.error("migrateStaleBlockers error:", err);
        return {
            statusCode: 500,
            body: { error: err?.message || String(err), phase, ms: Date.now() - t0 },
        };
    }
};
