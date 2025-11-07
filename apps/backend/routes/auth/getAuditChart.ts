import { db, event, graphqlQuery } from '#src/utils';

export const getAuditChart = async () => {
    const auditId = event.queryStringParameters.id;
    const days = parseInt((event.queryStringParameters as any).days || '7', 10);

    await db.connect();
    const audit = (await db.query({
        text: `SELECT * FROM "audits" WHERE "id" = $1`,
        values: [auditId],
    })).rows?.[0];
    await db.clean();

    // Query to get all scans for the audit with blocker counts
    const query = {
        query: `query ($audit_id: uuid!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: asc}) {
      id
      created_at
      blockers_aggregate {
        aggregate {
          count
        }
      }
    }
  }
}`,
        variables: { audit_id: auditId },
    };
    
    console.log(JSON.stringify({ query }));
    const response = await graphqlQuery(query);
    console.log(JSON.stringify({ response }));

    const scans = response.audits_by_pk?.scans || [];
    
    // Process scans to get the last scan per day
    const scansByDate = new Map<string, { date: string; blockers: number; timestamp: string }>();
    
    scans.forEach(scan => {
        const scanDate = new Date(scan.created_at);
        const dateKey = scanDate.toISOString().split('T')[0]; // YYYY-MM-DD format
        const blockerCount = scan.blockers_aggregate?.aggregate?.count || 0;
        
        // Only keep the last scan for each day (scans are ordered by created_at asc)
        scansByDate.set(dateKey, {
            date: dateKey,
            blockers: blockerCount,
            timestamp: scan.created_at
        });
    });

    // Generate array of the last N days
    const now = new Date();
    now.setUTCHours(0, 0, 0, 0); // Reset to start of day in UTC
    const chartData = [];
    let lastKnownValue = 0;

    for (let i = days - 1; i >= 0; i--) {
        const date = new Date(now);
        date.setUTCDate(date.getUTCDate() - i);
        const dateKey = date.toISOString().split('T')[0];
        
        if (scansByDate.has(dateKey)) {
            // Use the actual scan data for this day
            const scanData = scansByDate.get(dateKey)!;
            lastKnownValue = scanData.blockers;
            chartData.push({
                date: dateKey,
                blockers: scanData.blockers,
                timestamp: scanData.timestamp
            });
        } else {
            // Fill with the last known value
            if(days>30){ // If the range is >30 days, only return weekly points
                if(i % 7 === 0){
                    chartData.push({
                        date: dateKey,
                        blockers: lastKnownValue,
                        timestamp: null
                    });
                }
            }else{
                chartData.push({
                        date: dateKey,
                        blockers: lastKnownValue,
                        timestamp: null
                });
            }
        }
    }

    return {
        statusCode: 200,
        headers: { 'content-type': 'application/json' },
        body: {
            audit_id: auditId,
            audit_name: audit?.name,
            period_days: days,
            data: chartData,
        },
    };
}