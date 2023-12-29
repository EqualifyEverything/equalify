<?php
function build_join_and_select_clauses($filters, $columns = [], $joined_columns = []) {
    $joinClauses = "";
    $selectColumns = [];
    $joinedTables = []; // To track tables already joined

    // Check for tags filter and add join with tag_relationships table
    if (!empty($filters['tags'])) {
        $joinClauses .= " LEFT JOIN tag_relationships tr ON o.occurrence_id = tr.occurrence_id";
    }

    foreach ($joined_columns as $fullColumnName) {
        list($tableSingular, $column) = explode('_', $fullColumnName, 2);
        $table = $tableSingular . 's'; // Pluralize the table name
        $alias = substr($tableSingular, 0, 1); // Use the first letter as an alias

        // Add join clause only if this table hasn't been joined yet
        if (!isset($joinedTables[$table])) {
            $joinClauses .= " LEFT JOIN $table $alias ON o.occurrence_{$tableSingular}_id = $alias.{$tableSingular}_id";
            $joinedTables[$table] = true;
        }

        $selectColumns[] = "$alias.$fullColumnName";
    }

    // Add columns from occurrences
    foreach ($columns as $column) {
        $selectColumns[] = "o.$column";
    }

    $selectClause = implode(', ', $selectColumns);

    return [$joinClauses, $selectClause];
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

function count_total_occurrences($filters = [], $columns = [], $joined_columns = []) {
    global $pdo;

    list($joinClauses) = build_join_and_select_clauses($filters, $columns, $joined_columns);
    $whereClauses = build_where_clauses($filters);

    $count_sql = "
        SELECT 
            COUNT(DISTINCT o.occurrence_id)
        FROM 
            occurrences o
        $joinClauses
        $whereClauses
    ";

    $stmt = $pdo->query($count_sql);
    return $stmt->fetchColumn();
}

function fetch_occurrences($results_per_page, $offset, $filters = [], $columns = [], $joined_columns = []) {
    global $pdo;

    list($joinClauses, $selectClause) = build_join_and_select_clauses($filters, $columns,  $joined_columns);
    $whereClauses = build_where_clauses($filters);

    $sql = "
        SELECT 
            $selectClause
        FROM 
            occurrences o
        $joinClauses
        $whereClauses
        ORDER BY
            o.occurrence_status ASC
        LIMIT $results_per_page OFFSET $offset
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_results($results_per_page, $offset, $filters = [], $columns = [], $joined_columns = []) {
    global $pdo;

    $total_occurrences = count_total_occurrences($filters, $columns, $joined_columns);
    $occurrences = fetch_occurrences($results_per_page, $offset, $filters, $columns, $joined_columns);
    $total_pages = ceil($total_occurrences / $results_per_page);

    return [
        'occurrences' => $occurrences,
        'totalPages' => $total_pages
    ];
}
