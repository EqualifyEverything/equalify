import { db, event, graphqlQuery } from '#src/utils';
import { fetchAndValidateRemoteCsv } from '#src/routes/internal/fetchAndValidateRemoteCsv';


//
// Takes an AuditId for an audit with a remote CSV,
// and updates the audit's URLs from remote
//

type newUrl = {
    url:string;
    type: string;
}

type DBUrl = {
    id: string;
    type: string;
    url: string
}

export const syncAuditUrlsFromRemoteCsv = async (auditId:string) => {
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

    const currentUrls = (await db.query({
        text: `SELECT "id", "url", "type" FROM "urls" WHERE "audit_id" = $1`,
        values: [auditId],
    })).rows;
    await db.clean();

    // cache the url objects for efficiency and also why not
    const existingKeys = new Set(
        currentUrls.map((item:DBUrl) => `${item.url}`)
    );
    const csvKeys = new Set(
        remoteCsvUrls.map((item:newUrl)=> `${item.url}|${item.type}`)
    );
    const csvKeysUrl = new Set(
        remoteCsvUrls.map((item:newUrl)=> `${item.url}`)
    );

    // get _new_ URLs to add
    const urlsToAdd = remoteCsvUrls.filter((item:newUrl)=>{
        const key = `${item.url}`;
        return !existingKeys.has(key); 
    });

    // get URLs to remove
    const urlsToRemove = currentUrls.filter((item:DBUrl)=>{
        const key = `${item.url}`;
        return !csvKeysUrl.has(key); 
    });

    // get URLs to updated
    const urlsToUpdate = currentUrls.filter((item:DBUrl)=>{
        const key = `${item.url}|${item.type}`;
        return csvKeysUrl.has(item.url) && !csvKeys.has(key);
    });

    // union with existing URLs
    return {
        currentUrls,
        remoteCsvUrls,
        urlsToAdd,
        urlsToRemove,
        urlsToUpdate
    }
}
