<?php
// Retrieve values from the URL query parameter
$report_id = $_GET['report_id'] ?? '';
$filtersArray = isset($_GET['filters']) ? $_GET['filters'] : null;

// Cookie name
$cookie_name = "unsaved_report_" . $report_id . "_filters";

// Retrieve existing filters from cookie or initialize a new array
if (isset($_COOKIE[$cookie_name])) {
    // Cookie exists, retrieve and decode it
    $filters = json_decode($_COOKIE[$cookie_name], true) ?? [];
} else {
    // No cookie, initialize an empty array for filters
    $filters = [];
}

// Process single filter
if (isset($_GET['filter_id'], $_GET['filter_type'], $_GET['filter_value'])) {
    $new_filter = [
        'filter_type' => $_GET['filter_type'],
        'filter_value' => $_GET['filter_value'],
        'filter_id' => $_GET['filter_id'],
    ];
    $filters[] = $new_filter;
}

// Process array of filters
if (is_array($filtersArray)) {
    foreach ($filtersArray as $filterString) {
        // Decode the URL-encoded filter string
        $decodedFilterString = urldecode($filterString);
        parse_str($decodedFilterString, $filterData);
        if (isset($filterData['filter_type'], $filterData['filter_value'], $filterData['filter_id'])) {
            $filters[] = [
                'filter_type' => $filterData['filter_type'],
                'filter_value' => $filterData['filter_value'],
                'filter_id' => $filterData['filter_id'],
            ];
        }
    }
}

// Set or update the cookie
setcookie($cookie_name, json_encode($filters),  time() + strtotime( '+30 days' ), '/');

// Redirect the user to the report page with the report ID
header("Location: ../index.php?view=report&report_id=" . urlencode($report_id));
exit;
?>
