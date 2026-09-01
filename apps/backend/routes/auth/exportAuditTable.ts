import { db, event, graphqlQuery, validateShortId, buildUrlSearchClause } from "#src/utils";

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

  // Mirrors getAuditTable: "all" | "group" (one row per unique hash) | "hide"
  // (only blockers appearing once in the latest scan).
  const duplicatesMode = (event.queryStringParameters as any).duplicates || "all";

  await db.connect();
  const audit = (
    await db.query({
      text: `SELECT * FROM "audits" WHERE "id" = $1`,
      values: [auditId],
    })
  ).rows?.[0];

  // Duplicated hashes in the latest scan (same definition as getAuditTable),
  // for the duplicated/hide filters and the Occurrences CSV column.
  const duplicateRows = (
    await db.query({
      text: `SELECT "content_hash_id", COUNT(*)::int AS "occurrences"
             FROM "blockers"
             WHERE "scan_id" = (SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1)
             GROUP BY "content_hash_id"
             HAVING COUNT(*) > 1`,
      values: [auditId],
    })
  ).rows as { content_hash_id: string; occurrences: number }[];
  await db.clean();

  const occurrencesByHash = new Map(
    duplicateRows.map((row) => [row.content_hash_id, row.occurrences])
  );
  const duplicatedHashIds = duplicateRows.map((row) => row.content_hash_id);

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
    } else if (statusParam === "duplicated") {
      whereConditions.push({
        content_hash_id: { _in: duplicatedHashIds },
      });
    }
  }

  if (duplicatesMode === "hide") {
    whereConditions.push({
      _not: { content_hash_id: { _in: duplicatedHashIds } },
    });
  }

  if (searchString !== "") {
    if (validateShortId(searchString)) {
      whereConditions.push({ short_id: { _eq: searchString } });
    } else {
      whereConditions.push(buildUrlSearchClause(searchString));
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
      body: "Type,URL,Issue,Code,Tags,Categories,Status,ID,Occurrences\n",
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
    content_hash_id
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
      content_hash_id: blocker.content_hash_id,
      occurrences: occurrencesByHash.get(blocker.content_hash_id) ?? 1,
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

  // Group mode: keep the first row per content hash (after the contentType
  // filter so the kept representative matches the requested type).
  if (duplicatesMode === "group") {
    const seenHashes = new Set<string>();
    formattedBlockers = formattedBlockers.filter((b) => {
      if (seenHashes.has(b.content_hash_id)) return false;
      seenHashes.add(b.content_hash_id);
      return true;
    });
  }

  // Occurrences is appended last so consumers addressing the export by
  // column position keep the original eight columns unchanged.
  const headers = [
    "Type",
    "URL",
    "Issue",
    "Code",
    "Tags",
    "Categories",
    "Status",
    "ID",
    "Occurrences",
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
      b.occurrences,
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
