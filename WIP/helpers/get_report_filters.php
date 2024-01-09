<?php
// Returns the report filters
function get_report_filters() {
    global $pdo;
    global $report_id;

    // Fetch filters from database
    $stmt = $pdo->prepare("SELECT report_filters FROM reports WHERE report_id = :report_id");
    $stmt->execute(['report_id' => $report_id]);
    $db_raw_filters = $stmt->fetchColumn();
    $db_raw_filters = $db_raw_filters ? json_decode($db_raw_filters, true) : [];
    $db_filters = [];
    foreach ($db_raw_filters as $filter) {
        $db_filters[] = $filter;
    }

    // Cookie Name
    $cookie_name = 'queue_report_' . $report_id . '_filter_change';

    // Fetch filters from cookie
    $cookie_filters = isset($_COOKIE[$cookie_name]) ? json_decode($_COOKIE[$cookie_name], true) : [];

    // Merged cookie filters and DB filters
    $merged_filters = array_merge($db_filters, $cookie_filters);

    // Build a map of the latest filter_change directives for each unique filter
    $latest_directives = [];
    foreach ($merged_filters as $item) {
        if (isset($item['filter_change'])) {
            $key = $item['filter_type'] . '_' . $item['filter_id'] . '_' . $item['filter_value'];
            $latest_directives[$key] = $item['filter_change'];
        }
    }

    // Apply the latest directives
    $result_as_array = [];
    foreach ($merged_filters as $item) {
        $key = $item['filter_type'] . '_' . $item['filter_id'] . '_' . $item['filter_value'];
        if (!isset($latest_directives[$key]) || $latest_directives[$key] === 'add') {
            unset($item['filter_change']);
            $result_as_array[] = $item;
        }
    }

    // Grouping for string representation
    $grouped = [];
    foreach ($result_as_array as $item) {
        $grouped[$item['filter_type']][] = $item['filter_id'];
    }

    // Building the query string
    $query_string_parts = [];
    foreach ($grouped as $type => $ids) {
        $query_string_parts[] = $type . '=' . implode(',', $ids);
    }
    $result_as_string = implode('&', $query_string_parts);

    // Return results
    return array(
        'as_string' => $result_as_string,
        'as_array'  => $result_as_array
    );
}
?>