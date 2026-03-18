import { db, event, graphqlQuery } from '#src/utils';
import { syncAuditUrlsFromRemoteCsv } from '../internal/syncAuditUrlsFromRemoteCsv';


//
// Takes an AuditId for an audit with a remote CSV,
// and updates the audit's URLs from remote
//

export const syncFromRemoteCsv = async () => {
    const auditId = (event.queryStringParameters as any).id;
    return await syncAuditUrlsFromRemoteCsv(auditId);
}
