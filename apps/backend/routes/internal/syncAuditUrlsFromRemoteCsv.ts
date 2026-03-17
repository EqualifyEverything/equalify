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

    // removed URLs, also clean up orphaned blockers and related rows
    if (urlsToRemove.length > 0) {
      const removedUrlIds = urlsToRemove.map((u: DBUrl) => u.id);

      await db.connect();
      // delete blocker_messages and ignored_blockers for blockers referencing removed URLs
      await db.query({
        text: `DELETE FROM "blocker_messages" WHERE "blocker_id" IN (SELECT "id" FROM "blockers" WHERE "url_id" = ANY($1::uuid[]))`,
        values: [removedUrlIds],
      });
      await db.query({
        text: `DELETE FROM "ignored_blockers" WHERE "blocker_id" IN (SELECT "id" FROM "blockers" WHERE "url_id" = ANY($1::uuid[]))`,
        values: [removedUrlIds],
      });
      // delete the orphaned blockers themselves
      await db.query({
        text: `DELETE FROM "blockers" WHERE "url_id" = ANY($1::uuid[])`,
        values: [removedUrlIds],
      });
      await db.clean();

      // then delete the URLs
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
