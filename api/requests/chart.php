<?php
function get_results($results_per_page = '', $offset = '', $filters = []) {
    global $pdo;
    
    // First we'll get the active ids
    $occurrence_ids_query = "
        SELECT DISTINCT tr.occurrence_id 
        FROM tag_relationships tr
        JOIN occurrences o ON tr.occurrence_id = o.occurrence_id
        WHERE 1=1 
    ";

    // Add conditions based on filters
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $occurrence_ids_query .= " AND tr.tag_id IN ($tagIds)";
    }
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $occurrence_ids_query .= " AND o.occurrence_message_id IN ($messageIds)";
    }
    if (!empty($filters['properties'])) {
        $propertyIds = implode(',', array_map('intval', $filters['properties']));
        $occurrence_ids_query .= " AND o.occurrence_property_id IN ($propertyIds)";
    }
    if (!empty($filters['pages'])) {
        $pageIds = implode(',', array_map('intval', $filters['pages']));
        $occurrence_ids_query .= " AND o.occurrence_page_id IN ($pageIds)";
    }

    // Occurrence Ids query
    $stmt = $pdo->prepare($occurrence_ids_query);
    $stmt->execute();
    $occurrence_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $occurrence_id_list = implode(
        ',',  
        array_map(
            function ($row) {
                return $row['occurrence_id'];
            }, 
            $occurrence_ids
        )
    );

    // Construct the SQL query with filters
    if(!empty($occurrence_id_list)){
        $updates_sql = "
        SELECT 
            month_year,
            update_message,
            occurrence_id
        FROM (
            SELECT 
                DATE_FORMAT(u.date_created, '%Y-%m') AS month_year,
                u.update_message,
                o.occurrence_id,
                ROW_NUMBER() OVER(PARTITION BY o.occurrence_id, DATE_FORMAT(u.date_created, '%Y-%m') ORDER BY u.date_created DESC) as rn
            FROM 
                updates u
            JOIN occurrences o ON u.occurrence_id = o.occurrence_id
            WHERE
                o.occurrence_id IN ($occurrence_id_list)
        ";
        
        if (!empty($filters['statuses'])) {
            $statuses = is_array($filters['statuses']) ? $filters['statuses'] : explode(',', $filters['statuses']);
            $statuses = array_map(function($status) {
                return $status === 'active' ? 'activated' : preg_replace("/[^a-zA-Z0-9_\-]+/", "", $status);
            }, $statuses);
            $statusList = "'" . implode("', '", $statuses) . "'";
            $updates_sql .= " AND u.update_message IN ($statusList)";
        }
        
        $updates_sql .= "
            ) as sub
            WHERE rn = 1
            GROUP BY month_year, update_message, occurrence_id
            ORDER BY month_year ASC
        ";

        // Updates query
        $stmt = $pdo->prepare($updates_sql);
        $stmt->execute();
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }else{
        $updates = '';
    }

    if (empty($updates)) {
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
    $minDate = min(array_column($updates, 'month_year'));
    $maxDate = max(array_column($updates, 'month_year'));

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

    foreach ($updates as $row) {
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