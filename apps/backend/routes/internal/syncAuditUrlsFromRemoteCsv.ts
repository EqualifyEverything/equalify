import { db, event, graphqlQuery } from "#src/utils";
import { fetchAndValidateRemoteCsv } from "#src/routes/internal/fetchAndValidateRemoteCsv";

//
// Takes an AuditId for an audit with a remote CSV,
// and updates the audit's URLs from remote
//

type newUrl = {
  url: string;
  type: string;
};

type DBUrl = {
  id: string;
  type: string;
  url: string;
};

export const syncAuditUrlsFromRemoteCsv = async (auditId: string) => {
  let message = "";
  try {
    if (!auditId) throw new Error("Invalid Audit ID!");

    // fetch audit
    await db.connect();
    const audit = (
      await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [auditId],
      })
    ).rows?.[0];
    const remoteCsvUrl = audit.remote_csv_url;

    if (!audit.remote_csv_url.trim()) {
      // doesn't use remote csv, skip
      console.log(`Audit ${auditId} doesn't use remote CSV, skipping sync`);
      return;
    }

    // fetch CSV URLs
    const remoteCsv = await fetchAndValidateRemoteCsv(remoteCsvUrl);
    if (!remoteCsv.data) {
      throw new Error("No URLs found in remote CSV.");
    }
    // Also reject empty arrays — a transient empty response (site briefly down,
    // CDN cache miss, plugin error) shouldn't be allowed to wipe every URL.
    // Previously this passed through and treated all current URLs as "removed".
    if (Array.isArray(remoteCsv.data) && remoteCsv.data.length === 0) {
      throw new Error("Remote CSV returned 0 URLs — refusing to sync (likely transient source error).");
    }
    const remoteCsvUrls = remoteCsv.data;

    const currentUrls = (
      await db.query({
        text: `SELECT "id", "url", "type" FROM "urls" WHERE "audit_id" = $1`,
        values: [auditId],
      })
    ).rows as DBUrl[];
    await db.clean();

    // cache the url objects for efficiency and also why not
    const existingKeys = new Set(
      currentUrls.map((item: DBUrl) => `${item.url}`),
    );
    const csvKeys = new Set(
      remoteCsvUrls.map((item: newUrl) => `${item.url}|${item.type}`),
    );
    const csvKeysUrl = new Set(
      remoteCsvUrls.map((item: newUrl) => `${item.url}`),
    );

    // get _new_ URLs to add
    const urlsToAdd = remoteCsvUrls.filter((item: newUrl) => {
      const key = `${item.url}`;
      return !existingKeys.has(key);
    });

    // get URLs to remove
    const urlsToRemove = currentUrls.filter((item: DBUrl) => {
      const key = `${item.url}`;
      return !csvKeysUrl.has(key);
    });

    // get URLs to updated and generate an array with the updated values
    const urlsToUpdate = currentUrls.filter((item: DBUrl) => {
      const key = `${item.url}|${item.type}`;
      return csvKeysUrl.has(item.url) && !csvKeys.has(key);
    });
    urlsToUpdate.forEach((el: DBUrl, index: number, arr: DBUrl[]) => {
      const updatedValue = remoteCsvUrls.find(
        (instance) => instance.url === el.url,
      );
      if (updatedValue) arr[index].type = updatedValue.type;
    });

    //
    // Store updates in db
    //

    // new URLs
    for (const url of urlsToAdd) {
      await graphqlQuery({
        query: `mutation ($audit_id: uuid, $url: String, $type: String) {
                insert_urls_one(object: {audit_id: $audit_id, url: $url, type: $type}) {id}
            }`,
        variables: {
          audit_id: auditId,
          url: url.url,
          type: url.type,
        },
      });
    }

    // Remove URLs that aren't in the CSV anymore. NOTE: we deliberately do NOT
    // cascade-delete the blockers that referenced these URLs — those are historical
    // findings and should be preserved. The blockers' url_id will dangle (point to a
    // deleted URL row); blocker_summary_view LEFT JOINs urls so they render gracefully.
    // Previously this block did `DELETE FROM blockers WHERE url_id = ANY(...)` which
    // permanently destroyed scan history whenever a CSV's URL formats changed (e.g.,
    // trailing-slash differences). See incident around 2026-06-01.
    if (urlsToRemove.length > 0) {
      for (const url of urlsToRemove) {
        await graphqlQuery({
          query: `mutation($audit_id:uuid,$url:String) {delete_urls(where: {audit_id: {_eq: $audit_id}, url: {_eq: $url}}) {affected_rows}}`,
          variables: {
            audit_id: auditId,
            url: url.url,
          },
        });
      }
    }

    // updated URLs
    for (const url of urlsToUpdate) {
      await graphqlQuery({
        query: `
        mutation ($audit_id: uuid, $url: String, $type: String) {
            update_urls(
                where: {
                    audit_id: {_eq: $audit_id},
                    _and: {url: {_eq: $url}}
                }, _set: {type: $type}
            ) 
                {
                    returning {
                        audit_id
                        type
                        updated_at
                        url
                        user_id
                }
            }
        }`,
        variables: {
          audit_id: auditId,
          url: url.url,
          type: url.type,
        },
      });
    }
    message = `Sync complete. URLs: added ${urlsToAdd.length}, removed ${urlsToRemove.length}, updated ${urlsToUpdate.length}.`;
    await db.connect();
    await db.query({
      text: `UPDATE "audits" SET "remote_csv_error"=$1 WHERE "id"=$2`,
      values: [null, auditId],
    });
    await db.clean();
  } catch (error) {
    let theError = "";
    console.log("Error in syncAuditFromRemoteCSV", error);
    if (error instanceof Error) {
      theError = error.message;
    } else {
      theError = String(error);
    }
    await db.connect();
    await db.query({
      text: `UPDATE "audits" SET "remote_csv_error"=$1 WHERE "id"=$2`,
      values: [theError, auditId],
    });
    await db.clean();
  }

  return {
    message,
  };
};
