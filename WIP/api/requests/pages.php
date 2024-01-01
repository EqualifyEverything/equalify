<?php
function build_join_clauses($filters = []) {
    $joinClauses = "";
    if (!empty($filters['tags'])) {
        // Add join with tag_relationships table only if tags filter is used
        $joinClauses .= " LEFT JOIN tag_relationships tr ON o.occurrence_id = tr.occurrence_id";
    }
    return $joinClauses;
}

function build_where_clauses($filters = []) {
    $whereClauses = [];
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $whereClauses[] = "tr.tag_id IN ($tagIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "p.page_id IN ($pageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "o.occurrence_property_id IN ($propertyIds)";
    }
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "o.occurrence_message_id IN ($messageIds)";
    }
    if (!empty($filters['statuses'])) {
        $statuses = $filters['statuses'];
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
        $sanitizedStatuses = array_map(function($status) {
            return preg_replace("/[^a-zA-Z0-9_\-]+/", "", $status);
        }, $statuses);
        $whereClauses[] = "o.occurrence_status IN ('" . implode("', '", $sanitizedStatuses) . "')";
    }
    return $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
}

function count_total_pages($filters = []) {
    global $pdo;

    $whereClauses = build_where_clauses($filters);
    $joinClauses = build_join_clauses($filters);

    $count_sql = "
        SELECT 
            COUNT(DISTINCT p.page_id) 
            FROM             
            pages p
        INNER JOIN             
            occurrences o ON p.page_id = o.occurrence_page_id
        $joinClauses
        $whereClauses
    ";
    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_pages($results_per_page, $offset, $filters = []) {
    global $pdo;

    $whereClauses = build_where_clauses($filters);
    $joinClauses = build_join_clauses($filters);

    $sql = "
        SELECT             
            p.page_id, 
            p.page_url,
            SUM(CASE WHEN o.occurrence_status = 'active' THEN 1 ELSE 0 END) AS page_occurrences_active
        FROM             
            pages p
        INNER JOIN             
            occurrences o ON p.page_id = o.occurrence_page_id
        $joinClauses
        $whereClauses
        GROUP BY p.page_id        
        ORDER BY page_occurrences_active DESC
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page, $offset, $filters = []) {
    $total_pages = count_total_pages($filters);
    $pages = fetch_pages($results_per_page, $offset, $filters);
    $total_pages_count = ceil($total_pages / $results_per_page);

    return [
        'pages' => $pages,
        'totalPages' => $total_pages_count
    ];
}
