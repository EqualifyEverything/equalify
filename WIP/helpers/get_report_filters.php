<?php


So now  I have to:

Compare dbFilters with cookieFilters and..
1. remove any filter with the "filter_change" "remove"
2. add any filter with the "filter_change" "add"
3. create an array of the final result

// Returns the report filters
function get_report_filters($report_id) {

    // Include the database connection and utility functions
    require_once('db.php');
    
    // Fetch filters from database
    $stmt = $pdo->prepare("SELECT report_filters FROM reports WHERE report_id = :report_id");
    $stmt->execute(['report_id' => $report_id]);
    $dbRawFilters = $stmt->fetchColumn();
    $dbRawFilters = $dbRawFilters ? json_decode($dbRawFilters, true) : [];
    $dbFilters = [];
    foreach ($dbRawFilters as $filter) {
        $filter['filter_source'] = 'db'; // Add a source key
        $dbFilters[] = $filter;
    }

    // Cookie Name
    $cookieName = 'queue_report_' . $report_id . '_filter_change';

    // Fetch filters from cookie
    $cookieRawFilters = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];
    $cookieFilters = [];
    foreach ($cookieRawFilters as $filter) {
        $filter['filter_source'] = 'cookie'; // Add a source key
        $cookieFilters[] = $filter;
    }
    
    // Merged cookie filters and DB filters
    $result_as_array = array_merge($dbFilters, $cookieFilters);

    // Grouping for string representation
    $grouped = [];

    foreach ($result_as_array as $item) {
        $grouped[$item['filter_type']][] = $item['filter_id'];
    }

    // Building the query string
    $queryStringParts = [];
    foreach ($grouped as $type => $ids) {
        $queryStringParts[] = $type . '=' . implode(',', $ids);
    }
    $result_as_string = implode('&', $queryStringParts);

    // Return results
    return array(
        'as_string' => $result_as_string,
        'as_array'  => $result_as_array
    );
}
?>
