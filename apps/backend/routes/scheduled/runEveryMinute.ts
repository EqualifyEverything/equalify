import { db, queueScanJobs, closeOutScanUrls, finalizeScan, MAX_URL_ERROR_ENTRIES } from "#src/utils";
import {
  processScheduledAuditEmails,
  syncAuditUrlsFromRemoteCsv,
} from "../internal";

// A 'processing' scan whose row hasn't changed in this long is treated as stuck.
// Every scanWebhook call touches the row (processed_pages/percentage/errors), so
// this measures inactivity rather than total runtime: a 6,000-URL audit
// legitimately runs for hours at roughly 4 seconds per page. Raised from 15
// minutes after large audits were being flagged while still draining (#660).
const STUCK_SCAN_INACTIVITY_MINUTES =
  Math.max(1, Math.round(Number(process.env.STUCK_SCAN_INACTIVITY_MINUTES)) || 60);

export const runEveryMinute = async () => {
  // Determine whether we should run scheduled audits
  await db.connect();
  const scheduledAuditIds = (
    await db.query({
      text: `SELECT "id" FROM "audits" 
               WHERE 
                 EXTRACT(HOUR FROM "scheduled_at") = EXTRACT(HOUR FROM NOW())
                 AND EXTRACT(MINUTE FROM "scheduled_at") = EXTRACT(MINUTE FROM NOW())
                 AND (
                   ("interval" = 'Daily')
                   OR ("interval" = 'Weekly' AND EXTRACT(DOW FROM "scheduled_at") = EXTRACT(DOW FROM NOW()))
                   OR (
                     "interval" = 'Monthly' 
                     AND (
                       ("interval" = 'Daily')
                       OR ("interval" = 'Weekly' AND EXTRACT(DOW FROM "scheduled_at") = EXTRACT(DOW FROM NOW()))
                       OR (
                         "interval" = 'Monthly'
                         AND (
                           EXTRACT(DAY FROM "scheduled_at") = EXTRACT(DAY FROM NOW())
                           OR (
                             EXTRACT(DAY FROM "scheduled_at") >= 29
                             AND EXTRACT(DAY FROM NOW()) = EXTRACT(DAY FROM (DATE_TRUNC('MONTH', NOW()) + INTERVAL '1 MONTH - 1 DAY'))
                           )
                         )
                       )
                     )
                   )
                 )`,
    })
  ).rows.map((obj: { id: string; }) => obj.id);
  for (const scheduledAuditId of scheduledAuditIds) {
    // isolate each audit so one failure can't starve the rest of the batch
    try {
      // hook to check for remote CSV
      await syncAuditUrlsFromRemoteCsv(scheduledAuditId);

      const urls = (
        await db.query({
          text: `SELECT * FROM "urls" WHERE "audit_id"=$1`,
          values: [scheduledAuditId],
        })
      ).rows;

      // Skip scheduled audits with no URLs to prevent hung scans
      if (!urls || urls.length === 0) {
        console.log("Skipping scheduled audit with no URLs:", scheduledAuditId);
        continue;
      }

      const scanId = (
        await db.query({
          text: `INSERT INTO "scans" ("audit_id", "status", "pages") VALUES ($1, $2, $3) RETURNING "id"`,
          values: [
            scheduledAuditId,
            "processing",
            // `pages` snapshots the url ids too, so the stuck-scan sweep can tell
            // exactly which queued pages never reported
            JSON.stringify(urls.map((obj: { id: string; url: string; type: string; }) => ({ id: obj.id, url: obj.url, type: obj.type }))),
          ],
        })
      ).rows[0].id;
      await queueScanJobs({ auditId: scheduledAuditId, scanId, urls });
      console.log("Scan jobs queued for audit:", scheduledAuditId);
    } catch (error) {
      console.error("Scheduled scan failed for audit:", scheduledAuditId, error);
    }
  }

  // See if there are any "stuck" scans that we should close out.
  // Scans of one audit share a FIFO message group, so a scan started while an
  // older one is still running just waits in the queue (no activity of its own)
  // until the older one drains, and only then starts receiving results. An older
  // sibling with activity inside the window - whatever its status, since the
  // moment it completes is exactly when the newer scan is about to get its first
  // result - therefore means "queued", not "stuck". That wait is what produced
  // the false timeouts in #660. A sibling whose only recent "activity" was its
  // own close-out by this sweep doesn't count, so a pile of dead scans closes
  // out in one pass instead of one window apart.
  let stuckScans: { id: string; audit_id: string; pages: { id?: string; url: string }[] | null; processed_pages: string[] | null }[] = [];
  try {
    stuckScans = (
      await db.query({
        text: `SELECT s."id", s."audit_id", s."pages", s."processed_pages"
                 FROM "scans" s
                 WHERE s."status" = 'processing'
                 AND s."updated_at" < NOW() - make_interval(mins => $1::int)
                 AND NOT EXISTS (
                   SELECT 1 FROM "scans" o
                   WHERE o."audit_id" = s."audit_id"
                   AND o."created_at" < s."created_at"
                   AND o."updated_at" >= NOW() - make_interval(mins => $1::int)
                   AND NOT (COALESCE(o."errors", '[]'::jsonb) @> '[{"type":"scan_timeout"}]'::jsonb)
                 )`,
        values: [STUCK_SCAN_INACTIVITY_MINUTES],
      })
    ).rows;
  } catch (error) {
    // don't let a lookup failure skip db.clean() and the email pass below
    console.error("Failed to look up stuck scans:", error);
  }

  for (const scan of stuckScans) {
    // isolate each scan so one failure (e.g. a deadlock) can't abort the whole run,
    // which would skip db.clean() and the email pass below
    try {
      // Work out which pages never reported back. Scans started since url ids were
      // added to the `pages` snapshot can be diffed exactly; older scans fall back
      // to mapping url strings through the audit's urls table (best effort, since
      // remote-CSV syncs may have changed those rows mid-scan).
      const pageList: { id?: string; url: string }[] = scan.pages ?? [];
      const totalPages = pageList.length;
      const processedIds = new Set<string>((scan.processed_pages ?? []).map(String));
      let unscanned: { id: string; url: string }[];
      if (totalPages > 0 && pageList.every((p) => typeof p.id === "string")) {
        unscanned = pageList
          .filter((p) => !processedIds.has(p.id as string))
          .map((p) => ({ id: p.id as string, url: p.url }));
      } else {
        const pageUrls = new Set<string>(pageList.map((p) => p.url));
        const urlRows: { id: string; url: string }[] = (
          await db.query({
            text: `SELECT "id", "url" FROM "urls" WHERE "audit_id" = $1 ORDER BY "created_at"`,
            values: [scan.audit_id],
          })
        ).rows;
        unscanned = urlRows.filter((u) => pageUrls.has(u.url) && !processedIds.has(u.id));
      }
      const unscannedCount = Math.max(unscanned.length, totalPages - processedIds.size);
      // `capped` tells the UI the per-URL list doesn't cover the full count: either it
      // was cut at MAX_URL_ERROR_ENTRIES, or (legacy scans) some pages couldn't be
      // matched to the audit's current url rows at all.
      const listedPages = Math.min(unscanned.length, MAX_URL_ERROR_ENTRIES);
      const capped = listedPages < unscannedCount;

      const timeoutError = {
        type: "scan_timeout",
        message:
          `Scan timed out after ${STUCK_SCAN_INACTIVITY_MINUTES} minutes with no progress. ` +
          `${unscannedCount.toLocaleString()} of ${totalPages.toLocaleString()} pages were never scanned.` +
          (capped ? ` ${listedPages.toLocaleString()} of them are listed below.` : ""),
        timestamp: new Date().toISOString(),
        details: {
          inactivityMinutes: STUCK_SCAN_INACTIVITY_MINUTES,
          totalPages,
          scannedPages: Math.max(0, totalPages - unscannedCount),
          unscannedPages: unscannedCount,
          listedPages,
          capped,
        },
      };

      // Claim + close-out + finalize in one transaction: if any step fails the scan
      // stays 'processing' and the next sweep retries it, instead of being left
      // 'complete' with no counts. The status guard on the claim means a concurrent
      // scanWebhook write (results still trickling in right up to the cutoff) can't
      // be clobbered, and an overlapping sweep run can't close the same scan twice.
      await db.query({ text: "BEGIN" });
      try {
        const updateResult = await db.query({
          text: `UPDATE "scans"
                       SET "status" = 'complete', "errors" = COALESCE("errors", '[]'::jsonb) || $1::jsonb
                       WHERE "id" = $2 AND "status" = 'processing'`,
          values: [JSON.stringify([timeoutError]), scan.id],
        });

        if (updateResult.rowCount === 0) {
          // Scan already completed/failed on its own between the SELECT and here - nothing to do
          await db.query({ text: "ROLLBACK" });
          continue;
        }

        // Close out the pages that never reported: a url_timeout entry each (so the
        // Errors drawer lists them) and counted as processed so progress reads 100%.
        // A result that still arrives later clears its own url_timeout entry (see
        // scanWebhook), so a late-draining scan self-corrects.
        await closeOutScanUrls({
          scanId: scan.id,
          urls: unscanned,
          type: "url_timeout",
          message: "Not scanned before the scan timed out",
        });

        // Freeze blocker_count/equalified_count and settle the audit status so the
        // chart and the frontend reflect what was actually scanned.
        const { blockerCount } = await finalizeScan({ scanId: scan.id, auditId: scan.audit_id });
        await db.query({ text: "COMMIT" });

        console.log(
          "Closed out stuck scan:",
          scan.id,
          "audit:",
          scan.audit_id,
          { totalPages, unscannedCount, blockerCount },
        );
      } catch (error) {
        await db.query({ text: "ROLLBACK" }).catch(() => undefined);
        throw error;
      }
    } catch (error) {
      console.error("Failed to process stuck scan:", scan.id, error);
    }
  }

  await db.clean();

  // Process scheduled audit email notifications
  try {
    await processScheduledAuditEmails();
  } catch (emailError) {
    console.error("Scheduled audit email processing failed:", emailError);
  }

  return;
};
