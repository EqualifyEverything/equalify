<?php
function build_where_clauses_for_tags($filters = []) {
    $whereClauses = [];
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $whereClauses[] = "t.tag_id IN ($tagIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "o.page_id IN ($pageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "o.property_id IN ($propertyIds)";
    }
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "o.message_id IN ($messageIds)";
    }
    if (!empty($filters['statuses'])) {
        $statuses = $filters['statuses'];
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
        $sanitizedStatuses = array_map(function($status) {
            return preg_replace("/[^a-zA-Z0-9_\-]+/", "", $status);
        }, $statuses);
        $whereClauses[] = "o.status IN ('" . implode("', '", $sanitizedStatuses) . "')";
    }
    return $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
}

function count_total_tags($pdo, $filters = []) {
    $whereClauses = build_where_clauses_for_tags($filters);
    $count_sql = "
        SELECT COUNT(DISTINCT t.tag_id)
        FROM tags t
        LEFT JOIN tag_relationships tr ON t.tag_id = tr.tag_id
        LEFT JOIN occurrences o ON tr.occurrence_id = o.occurrence_id
        $whereClauses
    ";
    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_tags($pdo, $results_per_page, $offset, $filters = []) {
    $whereClauses = build_where_clauses_for_tags($filters);
    $sql = "
        SELECT 
            t.tag_id,
            t.tag_name,
            COUNT(tr.occurrence_id) AS tag_reference_count
        FROM 
            tags t
        LEFT JOIN 
            tag_relationships tr ON t.tag_id = tr.tag_id
        LEFT JOIN 
            occurrences o ON tr.occurrence_id = o.occurrence_id
        $whereClauses
        GROUP BY t.tag_id
        ORDER BY tag_reference_count DESC, t.tag_id
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($pdo, $results_per_page, $offset, $filters = []) {
    $total_tags = count_total_tags($pdo, $filters);
    $tags = fetch_tags($pdo, $results_per_page, $offset, $filters);
    $total_pages = ceil($total_tags / $results_per_page);

    return [
        'tags' => $tags,
        'totalPages' => $total_pages
    ];
}
