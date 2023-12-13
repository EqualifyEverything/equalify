<?php
require_once('../db.php');

// Retrieve values from the URL query parameter
$filter_id = $_GET['filter_id'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_value = $_GET['filter_value'] ?? '';
$report_id = $_GET['report_id'];

// Cookie name
$cookie_name = "unsaved_report_" . $report_id . "_filters";

$filter_found_in_cookie = false;

// Check if a cookie exists for the report
if (isset($_COOKIE[$cookie_name])) {
    // Cookie exists, retrieve and decode it
    $filters = json_decode($_COOKIE[$cookie_name], true);
    if (is_array($filters)) {
        // Search for the filter in the cookie and remove it if found
        foreach ($filters as $key => $filter) {
            if ($filter['filter_type'] == $filter_type && $filter['filter_value'] == $filter_value && $filter['filter_id'] == $filter_id) {
                unset($filters[$key]);
                $filter_found_in_cookie = true;
                break;
            }
        }
        // Update the cookie
        setcookie($cookie_name, json_encode(array_values($filters)), time() + 86400, '/');
    }
}

// If filter not found in the cookie, check and remove from DB
if (!$filter_found_in_cookie) {

    $stmt = $pdo->prepare("SELECT report_filters FROM reports WHERE report_id = :report_id");
    $stmt->execute(['report_id' => $report_id]);
    $filters = $stmt->fetchColumn();
    $filters = json_decode($filters, true);

    $filters = array_filter($filters, function ($filter) use ($filter_id, $filter_type) {
        return !($filter['filter_id'] === $filter_id && $filter['filter_type'] === $filter_type);
    });

    $stmt = $pdo->prepare("UPDATE reports SET report_filters = :filters WHERE report_id = :report_id");
    $stmt->execute(['filters' => json_encode($filters), 'report_id' => $report_id]);
}

// Redirect the user to the report page with the report ID
header("Location: ../index.php?view=report&report_id=$report_id");
exit;
