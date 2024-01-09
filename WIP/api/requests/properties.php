<?php
function build_where_clauses_for_properties($filters = []) {
    $whereClauses = [];
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $whereClauses[] = "tr.tag_id IN ($tagIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "o.occurrence_page_id IN ($pageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "p.property_id IN ($propertyIds)";
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

function count_total_properties($filters = []) {
    global $pdo;

    $whereClauses = build_where_clauses_for_properties($filters);
    $count_sql = "
        SELECT 
            COUNT(DISTINCT p.property_id) 
        FROM 
            properties p 
        INNER JOIN 
            occurrences o ON p.property_id = o.occurrence_property_id 
        INNER JOIN 
        tag_relationships tr ON o.occurrence_id = tr.occurrence_id 
        $whereClauses
    ";
    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_properties($results_per_page, $offset, $filters = []) {
    global $pdo;

    $whereClauses = build_where_clauses_for_properties($filters);
    $sql = "
        SELECT 
            p.property_id,
            p.property_name
        FROM 
            properties p
        INNER JOIN 
            occurrences o ON p.property_id = o.occurrence_property_id
        INNER JOIN 
            tag_relationships tr ON o.occurrence_id = tr.occurrence_id
        $whereClauses
        GROUP BY p.property_id
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page, $offset, $filters = []) {
    $total_properties = count_total_properties($filters);
    $properties = fetch_properties($results_per_page, $offset, $filters);
    $total_pages = ceil($total_properties / $results_per_page);

    return [
        'properties' => $properties,
        'totalPages' => $total_pages
    ];
}
