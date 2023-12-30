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
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "m.message_id IN ($messageIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "o.occurrence_page_id IN ($pageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "o.occurrence_property_id IN ($propertyIds)";
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

function count_total_occurrences($filters = []) {
    global $pdo;

    $joinClauses = build_join_clauses($filters);
    $whereClauses = build_where_clauses($filters);

    $count_sql = "
        SELECT 
            COUNT(DISTINCT o.occurrence_id)
        FROM 
            occurrences o
        $joinClauses
        LEFT JOIN 
            messages m ON o.occurrence_message_id = m.message_id
        $whereClauses
    ";

    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_occurrences($results_per_page, $offset, $filters = []) {
    global $pdo;

    $joinClauses = build_join_clauses($filters);
    $whereClauses = build_where_clauses($filters);

    $sql = "
        SELECT 
            o.occurrence_id,
            o.occurrence_code_snippet,
            o.occurrence_status,
            m.message_id,
            m.message_title
        FROM 
            occurrences o
        $joinClauses
        LEFT JOIN 
            messages m ON o.occurrence_message_id = m.message_id
        $whereClauses
        ORDER BY
            o.occurrence_status ASC
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page, $offset, $filters = []) {

    $total_occurrences = count_total_occurrences($filters);
    $occurrences = fetch_occurrences($results_per_page, $offset, $filters);
    $total_pages = ceil($total_occurrences / $results_per_page);

    return [
        'occurrences' => $occurrences,
        'totalPages' => $total_pages
    ];
}
