<?php
function build_where_clauses_for_statuses($filters = []) {
    $whereClauses = [];
    $joinClauses = "";

    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $joinClauses .= " LEFT JOIN tag_relationships tr ON o.occurrence_id = tr.occurrence_id";
        $whereClauses[] = "tr.tag_id IN ($tagIds)";
    }

    // Other filters
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "o.occurrence_page_id IN ($pageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "o.occurrence_property_id IN ($propertyIds)";
    }
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "o.occurrence_message_id IN ($messageIds)";
    }

    $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

    return [$joinClauses, $whereSql];
}

function fetch_statuses($filters = []) {
    global $pdo;

    list($joinClauses, $whereClauses) = build_where_clauses_for_statuses($filters);
    
    $sql = "
        SELECT 
            o.occurrence_status,
            COUNT(*) AS count
        FROM 
            occurrences o
            $joinClauses
            $whereClauses
        GROUP BY o.occurrence_status
    ";

    $stmt = $pdo->prepare($sql); // Use prepare instead of query when using variables
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page = '', $offset = '', $filters = []) {
    global $pdo;

    $statusCounts = fetch_statuses($pdo, $filters);

    $statuses = [];
    foreach ($statusCounts as $row) {
        $statuses[$row['occurrence_status']] = (int)$row['count'];
    }

    return [
        'statuses' => $statuses
    ];
}
