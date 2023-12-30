<?php
function build_where_clauses_for_chart($filters = []) {
    $whereClauses = [];
    $joinClauses = "JOIN occurrences o ON u.occurrence_id = o.occurrence_id";

    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "o.occurrence_message_id IN ($messageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $whereClauses[] = "o.occurrence_property_id IN ($propertyIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $whereClauses[] = "o.occurrence_page_id IN ($pageIds)";
    }
    if (!empty($filters['statuses'])) {
        $statuses = is_array($filters['statuses']) ? $filters['statuses'] : explode(',', $filters['statuses']);
        $statuses = array_map(function($status) {
            // Correcting 'active' to 'activated'
            return $status === 'active' ? 'activated' : preg_replace("/[^a-zA-Z0-9_\-]+/", "", $status);
        }, $statuses);
        $statusList = "'" . implode("', '", $statuses) . "'";
        $whereClauses[] = "u.update_message IN ($statusList)";
    }
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $joinClauses .= " JOIN tag_relationships tr ON o.occurrence_id = tr.occurrence_id";
        $whereClauses[] = "tr.tag_id IN ($tagIds)";
    }

    $whereSql = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

    return [$joinClauses, $whereSql];
}

function get_results($results_per_page = '', $offset = '', $filters = []) {
    global $pdo;
    
    list($joinClauses, $whereClauses) = build_where_clauses_for_chart($filters);

    // Construct the SQL query with filters
    $sql = "
        SELECT 
            DATE_FORMAT(u.date_created, '%Y-%m') AS month_year,
            u.update_message,
            o.occurrence_id
        FROM 
            updates u
            $joinClauses
            $whereClauses
        GROUP BY month_year, u.update_message, o.occurrence_id
        ORDER BY month_year ASC
    ";

    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        // Handle the case where there are no results (perhaps return an empty chart or a default chart)
        return [
            'labels' => [],
            'datasets' => [
                ['label' => 'Equalified', 'data' => [], 'borderColor' => 'green', 'fill' => false],
                ['label' => 'Active', 'data' => [], 'borderColor' => 'red', 'fill' => false],
                ['label' => 'Ignored', 'data' => [], 'borderColor' => 'gray', 'fill' => false]
            ]
        ];
    }

    // Find the earliest and latest dates
    $minDate = min(array_column($results, 'month_year'));
    $maxDate = max(array_column($results, 'month_year'));

    // Generate a list of all months between minDate and maxDate
    $period = new DatePeriod(
        new DateTime($minDate),
        new DateInterval('P1M'),
        (new DateTime($maxDate))->modify('+1 month')
    );

    $months = [];
    foreach ($period as $date) {
        $months[$date->format('Y-m')] = ['equalified' => 0, 'activated' => 0, 'ignored' => 0];
    }

    // Track the latest status of each occurrence
    $occurrenceStatus = [];

    foreach ($results as $row) {
        $monthYear = $row['month_year'];
        $status = $row['update_message'];
        $occurrenceId = $row['occurrence_id'];

        // Update the occurrence status
        $previousStatus = $occurrenceStatus[$occurrenceId] ?? null;
        $occurrenceStatus[$occurrenceId] = $status;

        // Update counts for this month and future months
        foreach ($months as $month => &$counts) {
            if ($month < $monthYear) {
                continue;
            }

            // Add to the new status count
            $counts[$status]++;

            // If this occurrence was previously counted under a different status, subtract one from that status
            if ($previousStatus && $previousStatus != $status) {
                $counts[$previousStatus]--;
            }
        }
    }

    // Prepare labels and datasets
    $labels = array_keys($months);
    $equalified_data = array_column($months, 'equalified');
    $active_data = array_column($months, 'activated');
    $ignored_data = array_column($months, 'ignored');

    
    $datasets = [];
    
    // Equalified dataset
    $datasets[] = [
        'label' => 'Equalified',
        'data' => $equalified_data,
        'borderColor' => 'green',
        'fill' => false
    ];
    
    // Active dataset
    $datasets[] = [
        'label' => 'Active',
        'data' => $active_data,
        'borderColor' => 'red',
        'fill' => false
    ];
    
    // Ignored dataset (if needed)
    $datasets[] = [
        'label' => 'Ignored',
        'data' => $ignored_data,
        'borderColor' => 'gray',
        'fill' => false
    ];

    $chart = [
        'labels' => $labels,
        'datasets' => $datasets
    ];
    
    return $chart;
}