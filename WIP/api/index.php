<?php
// Set content type
header('Content-Type: application/json');

// Include the database connection
require_once('../db.php');

// Define the base path for request files
define('REQUEST_BASE_PATH', __DIR__ . '/requests/');

// Handle various requests
$request = $_GET['request'] ?? '';

// Simplify getting query parameters
$results_per_page = $_GET['results_per_page'] ?? 5;
$page = isset($_GET['current_results_page']) ? (int)$_GET['current_results_page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Filters
$filters = [];
if (isset($_GET['tags'])) {
    $filters['tags'] = explode(',', $_GET['tags']);
}
if (isset($_GET['messages'])) {
    $filters['messages'] = explode(',', $_GET['messages']);
}
if (isset($_GET['pages'])) {
    $filters['pages'] = explode(',', $_GET['pages']);
}
if (isset($_GET['properties'])) {
    $filters['properties'] = explode(',', $_GET['properties']);
}
if (isset($_GET['statuses'])) {
    $filters['statuses'] = explode(',', $_GET['statuses']);
}
if (isset($_GET['occurrences'])) {
    $filters['occurrences'] = explode(',', $_GET['occurrences']);
}

// Determine the file and function to call based on the request type
$request_file = REQUEST_BASE_PATH . $request . '.php';

if (file_exists($request_file)) {
    require_once($request_file);
    $data = get_results($pdo, $results_per_page, $offset, $filters);
} else {
    http_response_code(400);
    $data = ['error' => 'Invalid request type'];
}

echo json_encode($data);