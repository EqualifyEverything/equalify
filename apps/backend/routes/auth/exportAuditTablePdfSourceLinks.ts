import { event, graphqlQuery } from "#src/utils";

const BATCH_SIZE = 1000;

const csvEscape = (val: any) => {
  const str = val === null || val === undefined ? "" : String(val);
  return `"${str.replace(/"/g, '""')}"`;
};

const extractHref = (content: string): string => {
  const match = content?.match(/href=["']([^"']+)["']/i);
  return match ? match[1] : "";
};

export const exportAuditTablePdfSourceLinks = async () => {
  const auditId = (event.queryStringParameters as any).id;

  const scanQuery = {
    query: `query ($audit_id: uuid!) {
  audits_by_pk(id: $audit_id) {
    name
    scans(order_by: {created_at: desc}, limit: 1) {
      id
    }
  }
}`,
    variables: { audit_id: auditId },
  };
  const scanResp = await graphqlQuery(scanQuery);
  const auditName = scanResp.audits_by_pk?.name;
  const latestScanId = scanResp.audits_by_pk?.scans?.[0]?.id;

  const datePart = new Date().toISOString().split("T")[0];
  const safeName = auditName ? auditName.replace(/[^a-z0-9-_]/gi, "_") + "-" : "";
  const filename = `pdf-links-${safeName}${auditId}-${datePart}.csv`;

  const emptyResponse = {
    statusCode: 200,
    headers: {
      "content-type": "text/csv; charset=utf-8",
      "content-disposition": `attachment; filename="${filename}"`,
    },
    body: "Source URL,PDF Link\n",
  };

  if (!latestScanId) return emptyResponse;

  const scopedWhere = {
    _and: [
      { scan_id: { _eq: latestScanId } },
      { url: { type: { _eq: "html" } } },
      { blocker_messages: { message: { category: { _eq: "pdf-link" } } } },
    ],
  };

  const rows: string[] = [];
  let offset = 0;
  while (true) {
    const batchQuery = {
      query: `query ($limit: Int!, $offset: Int!, $where: blockers_bool_exp!) {
  blockers(where: $where, limit: $limit, offset: $offset) {
    content
    url { url }
  }
}`,
      variables: { limit: BATCH_SIZE, offset, where: scopedWhere },
    };
    const batchResp = await graphqlQuery(batchQuery);
    const batch: any[] = batchResp.blockers || [];

    for (const blocker of batch) {
      const sourceUrl = blocker.url?.url || "";
      const pdfLink = extractHref(blocker.content);
      rows.push([csvEscape(sourceUrl), csvEscape(pdfLink)].join(","));
    }

    if (batch.length < BATCH_SIZE) break;
    offset += BATCH_SIZE;
  }

  const csv = ["Source URL,PDF Link", ...rows].join("\n");

  return {
    statusCode: 200,
    headers: {
      "content-type": "text/csv; charset=utf-8",
      "content-disposition": `attachment; filename="${filename}"`,
    },
    body: csv,
  };
};
