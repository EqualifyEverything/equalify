import { db, event, graphqlQuery } from '#src/utils';
import { fetchAndValidateRemoteCsv } from '#src/routes/internal/fetchAndValidateRemoteCsv';


//
// Takes an AuditId for an audit with a remote CSV,
// and updates the audit's URLs from remote
//

export const syncAuditUrlsFromRemoteCsv = async () => {
    const auditId = (event.queryStringParameters as any).id;
    if(!auditId) throw new Error("Invalid Audit ID!");
    
    // fetch audit
    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [auditId],
    })).rows?.[0];
    const remoteCsvUrl = audit.remote_csv_url;

    // fetch CSV URLs
    const remoteCsv = await fetchAndValidateRemoteCsv(remoteCsvUrl);
    if(!remoteCsv.data){
        throw new Error("No URLs found in remote CSV.")
    }
    const remoteCsvUrls = remoteCsv.data;

    const urls = (await db.query({
        text: `SELECT "id", "url", "type" FROM "urls" WHERE "audit_id" = $1`,
        values: [auditId],
    })).rows;
    await db.clean();

    // union with existing URLs
    return {
        urls,
        remoteCsvUrls
    }
}
