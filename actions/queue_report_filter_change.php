<?php
// Retrieve values from the URL query parameter
$report_id = $_GET['report_id'] ?? '';
$filters_array = isset($_GET['filters']) ? $_GET['filters'] : null;

// Cookie name
$cookie_name = "queue_report_" . $report_id . "_filter_change";

// Retrieve existing filters from cookie or initialize a new array
if (isset($_COOKIE[$cookie_name])) {
    // Cookie exists, retrieve and decode it
    $filters = json_decode($_COOKIE[$cookie_name], true) ?? [];
} else {
    // No cookie, initialize an empty array for filters
    $filters = [];
}

// Initialize an array to hold the latest filter change for each unique combination
$latest_filters = [];

// Process existing filters in the cookie
foreach ($filters as $filter) {
    $key = $filter['filter_type'] . '_' . $filter['filter_value'] . '_' . $filter['filter_id'];
    $latest_filters[$key] = $filter;
}

// Process the new filters received from the URL
foreach ($filters_array as $filter_string) {
    // Decode the URL-encoded filter string
    $decoded_filter_string = urldecode($filter_string);
    parse_str($decoded_filter_string, $filter_data);
    
    if (isset($filter_data['filter_type'], $filter_data['filter_value'], $filter_data['filter_id'])) {
        $key = $filter_data['filter_type'] . '_' . $filter_data['filter_value'] . '_' . $filter_data['filter_id'];
        $latest_filters[$key] = [
            'filter_type' => $filter_data['filter_type'],
            'filter_value' => $filter_data['filter_value'],
            'filter_id' => $filter_data['filter_id'],
            'filter_change' => $filter_data['filter_change'] ?? 'add' // Default to 'add' if not specified
        ];
    }
}

// Update the cookie with the latest filters
setcookie($cookie_name, json_encode(array_values($latest_filters)), time() + strtotime( '+30 days' ), '/');

// Redirect the user to the report page with the report ID
session_start();
$session_message = "Successfully changed filters.";
$_SESSION['success'] = $session_message;
header("Location: ../index.php?view=report_settings&report_id=" . urlencode($report_id));
exit;
?>
