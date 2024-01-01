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

function count_total_messages($filters = []) {
    global $pdo;

    $joinClauses = build_join_clauses($filters);
    $whereClauses = build_where_clauses($filters);

    $count_sql = "
        SELECT COUNT(DISTINCT m.message_id)
            FROM messages m 
        LEFT JOIN 
            occurrences o ON m.message_id = o.occurrence_message_id
        $joinClauses
        $whereClauses
    ";

    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_messages( $results_per_page, $offset, $filters = []) {
    global $pdo;

    $whereClauses = build_where_clauses($filters);
    $joinClauses = build_join_clauses($filters);

    $sql = "
        SELECT 
            m.message_id,
            m.message_title,
            SUM(o.occurrence_status = 'equalified') AS equalified_count,
            SUM(o.occurrence_status = 'active') AS active_count,
            SUM(o.occurrence_status = 'ignored') AS ignored_count,
            COUNT(o.occurrence_id) AS total_count
        FROM 
            messages m
        LEFT JOIN 
            occurrences o ON m.message_id = o.occurrence_message_id
        $joinClauses
        $whereClauses
        GROUP BY m.message_id
        ORDER BY SUM(o.occurrence_status = 'equalified') + SUM(o.occurrence_status = 'active') + SUM(o.occurrence_status = 'ignored') DESC, m.message_id
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page, $offset, $filters = []) {
    $total_messages = count_total_messages($filters);
    $messages = fetch_messages($results_per_page, $offset, $filters);
    $total_pages = ceil($total_messages / $results_per_page);

    return [
        'messages' => $messages,
        'totalPages' => $total_pages
    ];
}
