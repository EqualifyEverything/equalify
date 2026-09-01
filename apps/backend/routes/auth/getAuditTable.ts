import { db, event, graphqlQuery, validateShortId, buildUrlSearchClause } from "#src/utils";

export const getAuditTable = async () => {
  const auditId = (event.queryStringParameters as any).id;
  const page = parseInt((event.queryStringParameters as any).page || "0", 10);
  const pageSize = parseInt(
    (event.queryStringParameters as any).pageSize || "50",
    10
  );
  const contentType = (event.queryStringParameters as any).contentType || "all";
  const sortBy = (event.queryStringParameters as any).sortBy || "created_at";
  const sortOrder = (event.queryStringParameters as any).sortOrder || "desc";

  // Parse multiple filter parameters (comma-separated)
  const tagsParam = (event.queryStringParameters as any).tags || null;
  const categoriesParam =
    (event.queryStringParameters as any).categories || null;
  const statusParam = (event.queryStringParameters as any).status || null;

  const tagFilters = tagsParam ? tagsParam.split(",").filter(Boolean) : [];
  const typeFilters = categoriesParam
    ? categoriesParam.split(",").filter(Boolean)
    : [];

  const searchString = (event.queryStringParameters as any).searchString || "";

  // Duplicate handling: "all" shows every occurrence, "group" collapses to
  // one row per unique content_hash_id (distinct_on), "hide" drops blockers
  // that appear more than once in the latest scan.
  const duplicatesMode =
    (event.queryStringParameters as any).duplicates || "all";
  const dedupe = duplicatesMode === "group";

  await db.connect();
  const audit = (
    await db.query({
      text: `SELECT * FROM "audits" WHERE "id" = $1`,
      values: [auditId],
    })
  ).rows?.[0];

  // Duplicate groups in the latest scan: hashes appearing on more than one
  // row, with the pages they appear on. Powers the ×N occurrences chip, the
  // "duplicated" status filter, and per-blocker URL drilldown.
  const latestScanId = (
    await db.query({
      text: `SELECT "id" FROM "scans" WHERE "audit_id" = $1 ORDER BY "created_at" DESC LIMIT 1`,
      values: [auditId],
    })
  ).rows?.[0]?.id;

  // urls capped to 10 per group — the tooltip shows at most 10 and a "+N
  // more" line from url_count, so shipping full arrays (potentially thousands
  // of URLs per template blocker, repeated on every row) would bloat the
  // response for no UI benefit.
  const duplicateGroups: { content_hash_id: string; occurrences: number; url_count: number; urls: string[] }[] =
    latestScanId
      ? (
          await db.query({
            text: `SELECT "b"."content_hash_id", COUNT(*)::int AS "occurrences",
                          COUNT(DISTINCT COALESCE("u"."url", "b"."url_text", 'Unknown URL'))::int AS "url_count",
                          (ARRAY_AGG(DISTINCT COALESCE("u"."url", "b"."url_text", 'Unknown URL')))[1:10] AS "urls"
                   FROM "blockers" "b"
                   LEFT JOIN "urls" "u" ON "b"."url_id" = "u"."id"
                   WHERE "b"."scan_id" = $1
                   GROUP BY "b"."content_hash_id"
                   HAVING COUNT(*) > 1`,
            values: [latestScanId],
          })
        ).rows
      : [];
  await db.clean();

  const duplicateGroupsByHash = new Map(
    duplicateGroups.map((group) => [group.content_hash_id, group])
  );
  const duplicatedHashIds = duplicateGroups.map((group) => group.content_hash_id);

  // Build the where clause with multiple filters
  const whereConditions: any[] = [];

  // Tag filtering (OR condition - blocker has ANY of the selected tags)
  if (tagFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: {
          message_tags: {
            tag: {
              id: { _in: tagFilters },
            },
          },
        },
      },
    });
  }

  // Category filtering (OR condition - blocker has ANY of the selected categories)
  if (typeFilters.length > 0) {
    whereConditions.push({
      blocker_messages: {
        message: {
          category: { _in: typeFilters },
        },
      },
    });
  }

  // Content type filtering (html/pdf)
  if (contentType.toLowerCase() === "html" || contentType.toLowerCase() === "pdf") {
    whereConditions.push({
      url: { type: { _eq: contentType.toLowerCase() } },
    });
  }

  // Status filtering ('ignore' field true/false).
  // Filter directly on the blocker → ignored_blocker relationship — going through
  // blocker_messages forces a 20M-row sequential scan over that table.
  if (statusParam) {
    if (statusParam === "active") {
      whereConditions.push({
        _not: {
          ignored_blocker: {
            blocker_id: {
              _is_null: false,
            },
          },
        },
      });
    } else if (statusParam === "ignored") {
      whereConditions.push({
        ignored_blocker: {
          id: {
            _is_null: false,
          },
        },
      });
    } else if (statusParam === "duplicated") {
      // An empty _in list matches nothing, which is correct when no duplicates exist
      whereConditions.push({
        content_hash_id: { _in: duplicatedHashIds },
      });
    }
  }

  // Hide duplicated blockers entirely — only nodes appearing once remain.
  // Wrapped in _not so the status-count base clause keeps it: this is a view
  // mode, not a status, so all the dropdown counts should reflect it (the
  // strip below only removes bare content_hash_id status conditions).
  if (duplicatesMode === "hide") {
    whereConditions.push({
      _not: { content_hash_id: { _in: duplicatedHashIds } },
    });
  }

  // Add search string to where clause
  if (searchString !== "") {
    if (validateShortId(searchString)) {
      // if valid UUID, use that
      whereConditions.push({
        short_id: { _eq: searchString },
      });
    } else {
      // otherwise search URL
      whereConditions.push(buildUrlSearchClause(searchString));
    }
  }

  // Combine all conditions with AND
  const whereClause =
    whereConditions.length > 0 ? { _and: whereConditions } : {};

  // Build order_by clause based on sortBy parameter
  let orderByClause;
  if (sortBy === "url") {
    // Sort by the related url table's url field
    orderByClause = { url: { url: sortOrder } };
  } else {
    // Default to sorting by created_at or other fields on the blocker table
    orderByClause = { created_at: sortOrder };
  }

  // Build where clauses for status counts (excluding status filter)
  // Strip the active/ignored status condition so the count queries can apply
  // their own active/ignored filters independently.
  const baseWhereConditions = whereConditions.filter(
    (cond) =>
      !(
        cond.ignored_blocker ||
        cond._not?.ignored_blocker ||
        cond.content_hash_id
      )
  );
  const baseWhereClause =
    baseWhereConditions.length > 0 ? { _and: baseWhereConditions } : {};

  const activeWhereClause = {
    _and: [
      ...baseWhereConditions,
      {
        _not: {
          ignored_blocker: {
            blocker_id: {
              _is_null: false,
            },
          },
        },
      },
    ],
  };

  const ignoredWhereClause = {
    _and: [
      ...baseWhereConditions,
      {
        ignored_blocker: {
          id: {
            _is_null: false,
          },
        },
      },
    ],
  };

  const duplicatedWhereClause = {
    _and: [
      ...baseWhereConditions,
      { content_hash_id: { _in: duplicatedHashIds } },
    ],
  };

  // In dedupe mode, distinct_on requires order_by to lead with the distinct
  // column, and every count switches to distinct-by-hash so pagination and
  // the status dropdown stay consistent with what the table shows.
  const distinctClause = dedupe ? ", distinct_on: content_hash_id" : "";
  const countField = dedupe
    ? "count(columns: content_hash_id, distinct: true)"
    : "count";

  // Query to get blockers from the latest scan with pagination
  const query = {
    query: `query ($audit_id: uuid!, $limit: Int!, $offset: Int!, $where: blockers_bool_exp!, $order_by: [blockers_order_by!], $baseWhere: blockers_bool_exp!, $activeWhere: blockers_bool_exp!, $ignoredWhere: blockers_bool_exp!, $duplicatedWhere: blockers_bool_exp!) {
  audits_by_pk(id: $audit_id) {
    scans(order_by: {created_at: desc}, limit: 1) {
      id
      created_at
      blockers(where: $where, limit: $limit, offset: $offset, order_by: $order_by${distinctClause}) {
        id
        short_id
        content_hash_id
        created_at
        content
        url_id
        url_text
        url {
          url
          type
        }
        blocker_messages {
          id
          message {
            id
            content
            category
            message_tags {
              tag {
                id
                content
              }
            }
          }
        }
      }
      blockers_aggregate(where: $where) {
        aggregate {
          ${countField}
        }
      }
      all_blockers_count: blockers_aggregate(where: $baseWhere) {
        aggregate {
          ${countField}
        }
      }
      active_blockers_count: blockers_aggregate(where: $activeWhere) {
        aggregate {
          ${countField}
        }
      }
      ignored_blockers_count: blockers_aggregate(where: $ignoredWhere) {
        aggregate {
          ${countField}
        }
      }
      duplicated_blockers_count: blockers_aggregate(where: $duplicatedWhere) {
        aggregate {
          ${countField}
        }
      }
    }
  }
  tags(order_by: {content: asc}) {
    id
    content
  }
  messages(order_by: {category: asc}, distinct_on: category) {
    category
  }
}`,
    variables: {
      audit_id: auditId,
      limit: pageSize,
      offset: page * pageSize,
      where: whereClause,
      order_by: dedupe
        ? [{ content_hash_id: "asc" }, orderByClause]
        : [orderByClause],
      baseWhere: baseWhereClause,
      activeWhere: activeWhereClause,
      ignoredWhere: ignoredWhereClause,
      duplicatedWhere: duplicatedWhereClause,
    },
  };

  console.log(JSON.stringify({ query }));
  const response = await graphqlQuery(query);
  console.log(JSON.stringify({ response }));

  const latestScan = response.audits_by_pk?.scans?.[0];
  const blockers = latestScan?.blockers || [];
  const totalCount = latestScan?.blockers_aggregate?.aggregate?.count || 0;
  const allBlockersCount =
    latestScan?.all_blockers_count?.aggregate?.count || 0;
  const activeBlockersCount =
    latestScan?.active_blockers_count?.aggregate?.count || 0;
  const ignoredBlockersCount =
    latestScan?.ignored_blockers_count?.aggregate?.count || 0;
  const duplicatedBlockersCount =
    latestScan?.duplicated_blockers_count?.aggregate?.count || 0;
  const availableTags = response.tags || [];
  const availableCategories = response.messages || [];

  // Format the blockers data
  let formattedBlockers = blockers.map((blocker) => {
    // Extract tags from blocker_messages -> message -> message_tags -> tag
    const tags = blocker.blocker_messages.flatMap(
      (bm) => bm.message.message_tags?.map((mt) => mt.tag).filter(Boolean) || []
    );

    const uniqueTags = Array.from(
      new Map(tags.map((tag) => [tag.id, tag])).values()
    );

    // Extract categories from blocker_messages -> message -> category
    const categories = blocker.blocker_messages.map(
      (bm) => bm.message.category
    );
    const uniqueCategories = Array.from(new Set(categories));

    // Extract equalified status from blocker_messages -> blocker -> equalified
    /* const equalified = blocker.blocker_messages.length > 0 
            ? blocker.blocker_messages[0].blocker.equalified 
            : false; */

    // Extract message contents
    const messages = blocker.blocker_messages.map(
      (bm) => `[${bm.message.category}] ${bm.message.content}`
    );

    const duplicateGroup = duplicateGroupsByHash.get(blocker.content_hash_id);

    return {
      duplicateCount: duplicateGroup?.occurrences ?? 1,
      duplicateUrlCount: duplicateGroup?.url_count ?? 1,
      duplicateUrls: duplicateGroup?.urls ?? [],
      id: blocker.id,
      short_id: blocker.short_id,
      content_hash_id: blocker.content_hash_id,
      created_at: blocker.created_at,
      // Fallback chain: live join → snapshotted url_text (preserved at scan time) → Unknown URL.
      // This keeps the URL visible even if the urls row was later deleted (CSV change, manual removal).
      url: blocker.url?.url || blocker.url_text || "Unknown URL",
      type: blocker.url?.type || "unknown",
      url_id: blocker.url_id,
      content: blocker.content,
      ignore: blocker.ignore,
      //equalified: equalified,
      messages: messages,
      tags: uniqueTags,
      categories: uniqueCategories,
    };
  });

  return {
    statusCode: 200,
    headers: { "content-type": "application/json" },
    body: JSON.stringify({
      audit_id: auditId,
      audit_name: audit?.name,
      scan_date: latestScan?.created_at,
      blockers: formattedBlockers,
      pagination: {
        page,
        pageSize,
        totalCount,
        totalPages: Math.ceil(totalCount / pageSize),
      },
      statusCounts: {
        all: allBlockersCount,
        active: activeBlockersCount,
        ignored: ignoredBlockersCount,
        duplicated: duplicatedBlockersCount,
      },
      availableTags,
      availableCategories: availableCategories
        .map((m: any) => m.category)
        .filter(Boolean),
      filters: {
        tags: tagFilters,
        types: typeFilters,
        status: statusParam,
        duplicates: duplicatesMode,
      },
    }),
  };
};
