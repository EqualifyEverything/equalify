import { db, event, graphqlQuery, validateShortId } from "#src/utils";

const BATCH_SIZE = 1000;

const csvEscape = (val: any) => {
  const str = val === null || val === undefined ? "" : String(val);
  return `"${str.replace(/"/g, '""')}"`;
};

export const exportAuditTable = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const contentType = (event.queryStringParameters as any).contentType || "all";
  const sortBy = (event.queryStringParameters as any).sortBy || "created_at";
  const sortOrder = (event.queryStringParameters as any).sortOrder || "desc";

  const tagsParam = (event.queryStringParameters as any).tags || null;
  const categoriesParam =
    (event.queryStringParameters as any).categories || null;
  const statusParam = (event.queryStringParameters as any).status || null;

  const tagFilters = tagsParam ? tagsParam.split(",").filter(Boolean) : [];
  const typeFilters = categoriesParam
    ? categoriesParam.split(",").filter(Boolean)
    : [];

  const searchString = (event.queryStringParameters as any).searchString || "";

  await db.connect();
  const audit = (
    await db.query({
      text: `SELECT * FROM "audits" WHERE "id" = $1`,
      values: [auditId],
    })
  ).rows?.[0];
  await db.clean();

  const whereConditions: any[] = [];

  if (tagFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: {
          message_tags: { tag: { id: { _in: tagFilters } } },
        },
      },
    });
  }

  if (typeFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: { category: { _in: typeFilters } },
      },
    });
  }

  if (statusParam) {
    if (statusParam === "active") {
      whereConditions.push({
        blocker_messages: {
          blocker: {
            _not: {
              ignored_blocker: { blocker_id: { _is_null: false } },
            },
          },
        },
      });
    } else if (statusParam === "ignored") {
      whereConditions.push({
        blocker_messages: {
          blocker: {
            ignored_blocker: { id: { _is_null: false } },
          },
        },
      });
    }
  }

  if (searchString !== "") {
    if (validateShortId(searchString)) {
      whereConditions.push({ short_id: { _eq: searchString } });
    } else {
      whereConditions.push({
        url: { url: { _ilike: `%${searchString}%` } },
      });
    }
  }

  const whereClause =
    whereConditions.length > 0 ? { _and: whereConditions } : {};

  const orderByClause =
    sortBy === "url"
      ? { url: { url: sortOrder } }
      : { created_at: sortOrder };

  // Find the latest scan id for this audit so we can paginate blockers directly
  const scanQuery = {
    query: `query ($audit_id: uuid!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      id
    }
  }
}`,
    variables: { audit_id: auditId },
  };
  const scanResp = await graphqlQuery(scanQuery);
  const latestScanId = scanResp.audits_by_pk?.scans?.[0]?.id;

  if (!latestScanId) {
    return {
      statusCode: 200,
      headers: {
        "content-type": "text/csv; charset=utf-8",
        "content-disposition": `attachment; filename="blockers-${auditId}-${new Date().toISOString().split("T")[0]}.csv"`,
      },
      body: "Type,URL,Issue,Code,Tags,Categories,Status,ID\n",
    };
  }

  // Pull all blockers in batches to avoid Hasura row limits
  const ignoredSetQuery = {
    query: `query ($audit_id: uuid!) {
  ignored_blockers(where: {audit_id: {_eq: $audit_id}}) {
    blocker_id
  }
}`,
    variables: { audit_id: auditId },
  };
  const ignoredResp = await graphqlQuery(ignoredSetQuery);
  const ignoredSet = new Set<string>(
    (ignoredResp.ignored_blockers || []).map((ib: any) => ib.blocker_id)
  );

  const scopedWhere = {
    _and: [{ scan_id: { _eq: latestScanId } }, ...whereConditions],
  };

  const allBlockers: any[] = [];
  let offset = 0;
  while (true) {
    const batchQuery = {
      query: `query ($limit: Int!, $offset: Int!, $where: blockers_bool_exp!, $order_by: [blockers_order_by!]) {
  blockers(where: $where, limit: $limit, offset: $offset, order_by: $order_by) {
    id
    short_id
    created_at
    content
    url_id
    url { url type }
    blocker_messages {
      id
      message {
        id
        content
        category
        message_tags { tag { id content } }
      }
    }
  }
}`,
      variables: {
        limit: BATCH_SIZE,
        offset,
        where: scopedWhere,
        order_by: [orderByClause],
      },
    };
    const batchResp = await graphqlQuery(batchQuery);
    const batch = batchResp.blockers || [];
    allBlockers.push(...batch);
    if (batch.length < BATCH_SIZE) break;
    offset += BATCH_SIZE;
  }

  let formattedBlockers = allBlockers.map((blocker) => {
    const tags = blocker.blocker_messages.flatMap(
      (bm: any) =>
        bm.message.message_tags?.map((mt: any) => mt.tag).filter(Boolean) || []
    );
    const uniqueTags = Array.from(
      new Map(tags.map((tag: any) => [tag.id, tag])).values()
    ) as any[];
    const categories = Array.from(
      new Set(blocker.blocker_messages.map((bm: any) => bm.message.category))
    );
    const messages = blocker.blocker_messages.map(
      (bm: any) => `[${bm.message.category}] ${bm.message.content}`
    );
    return {
      id: blocker.id,
      short_id: blocker.short_id,
      url: blocker.url?.url || "Unknown URL",
      type: blocker.url?.type || "unknown",
      content: blocker.content,
      messages,
      tags: uniqueTags,
      categories,
    };
  });

  if (
    contentType.toLowerCase() === "html" ||
    contentType.toLowerCase() === "pdf"
  ) {
    formattedBlockers = formattedBlockers.filter(
      (b) => b.type.toLowerCase() === contentType.toLowerCase()
    );
  }

  const headers = [
    "Type",
    "URL",
    "Issue",
    "Code",
    "Tags",
    "Categories",
    "Status",
    "ID",
  ];
  const rows = formattedBlockers.map((b) =>
    [
      b.type,
      b.url,
      b.messages?.[0] || "",
      b.content || "",
      b.tags.map((t: any) => t.content).join("; "),
      b.categories.join("; "),
      ignoredSet.has(b.id) ? "Ignored" : "Active",
      b.short_id || "",
    ]
      .map(csvEscape)
      .join(",")
  );

  const csv = [headers.join(","), ...rows].join("\n");
  const datePart = new Date().toISOString().split("T")[0];
  const filename = `blockers-${audit?.name ? audit.name.replace(/[^a-z0-9-_]/gi, "_") + "-" : ""}${auditId}-${datePart}.csv`;

  return {
    statusCode: 200,
    headers: {
      "content-type": "text/csv; charset=utf-8",
      "content-disposition": `attachment; filename="${filename}"`,
    },
    body: csv,
  };
};
