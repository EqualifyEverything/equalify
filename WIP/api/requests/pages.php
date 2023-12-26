<?php
function build_where_clauses_for_pages($filters = []) {
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

function count_total_pages($pdo, $filters = []) {
    $whereClauses = build_where_clauses_for_pages($filters);
    $count_sql = "SELECT COUNT(DISTINCT p.page_id) FROM pages p LEFT JOIN occurrences o ON p.page_id = o.occurrence_page_id LEFT JOIN tag_relationships tr ON o.occurrence_id = tr.occurrence_id $whereClauses";
    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_pages($pdo, $results_per_page, $offset, $filters = []) {
    $whereClauses = build_where_clauses_for_pages($filters);
    $sql = "
        SELECT 
            p.page_id,
            p.page_url,
            COALESCE(SUM(o.occurrence_status = 'equalified') / COUNT(o.occurrence_status) * 100, 0) AS equalified_percentage
        FROM 
            pages p
        LEFT JOIN 
            occurrences o ON p.page_id = o.occurrence_page_id
        LEFT JOIN 
            tag_relationships tr ON o.occurrence_id = tr.occurrence_id
        $whereClauses
        GROUP BY p.page_id
        ORDER BY equalified_percentage ASC, p.page_id
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($pdo, $results_per_page, $offset, $filters = []) {
    $total_pages = count_total_pages($pdo, $filters);
    $pages = fetch_pages($pdo, $results_per_page, $offset, $filters);
    $total_pages_count = ceil($total_pages / $results_per_page);

    return [
        'pages' => $pages,
        'totalPages' => $total_pages_count
    ];
}
