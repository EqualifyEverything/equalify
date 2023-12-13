<?php
// Retrieve values from the URL query parameter
$report_id = $_GET['report_id'] ?? '';
$filtersArray = isset($_GET['filters']) ? $_GET['filters'] : null;

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

// Process array of filters
foreach ($filtersArray as $filterString) {
    // Decode the URL-encoded filter string
    $decodedFilterString = urldecode($filterString);
    parse_str($decodedFilterString, $filterData);
    if (isset($filterData['filter_type'], $filterData['filter_value'], $filterData['filter_id'], $filterData['filter_change'])) {
        $filters[] = [
            'filter_type' => $filterData['filter_type'],
            'filter_value' => $filterData['filter_value'],
            'filter_id' => $filterData['filter_id'],
            'filter_change' => $filterData['filter_change']
        ];
    }
}

// Set or update the cookie
setcookie($cookie_name, json_encode($filters), time() + 86400, '/');

// Redirect the user to the report page with the report ID
header("Location: ../index.php?view=report&report_id=" . urlencode($report_id));
exit;
?>
