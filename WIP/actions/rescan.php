<?php
// Include dependencies
require_once '../db.php';

// Add meta with meta_name rescan and value with $filters 

// Parse filters
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

// Build where clauses
function build_where_clauses($filters = []) {
    $whereClauses = [];
    if (!empty($filters['tags'])) {
        $tagIds = implode(',', array_map('intval', $filters['tags']));
        $whereClauses[] = "tr.tag_id IN ($tagIds)";
    }
    if (!empty($filters['messages'])) {
        $messageIds = implode(',', array_map('intval', $filters['messages']));
        $whereClauses[] = "o.occurrence_message_id IN ($messageIds)";
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

// Get URLs
$whereClauses = build_where_clauses($filters);
$sql = "
    SELECT DISTINCT
        pa.page_url, o.occurrence_property_id
    FROM 
        pages pa	
    INNER JOIN 
        occurrences o ON pa.page_id = o.occurrence_page_id
    $whereClauses
";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kill process if there's nothing to scan.
if (empty($results))
    exit;

// Send URL to scan
$api_url = 'http://198.211.98.156/generate/url';
foreach ($results as $index => &$row) {
    $data = json_encode(array("url" => $row['page_url']));  
    $ch = curl_init($api_url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    $response = curl_exec($ch);
    $response_data = json_decode($response, true);

    // Check if response data contains 'jobID'
    if (isset($response_data['jobID'])) {
        // Add the jobID to the corresponding element in $results
        $row['job_id'] = $response_data['jobID'];
    }
}

// Add results into scan queue.
$insertQuery = "INSERT INTO queued_scans (queued_scan_job_id, queued_scan_property_id, queued_scan_priority) VALUES ";
$insertValues = [];
$params = [];
foreach ($results as $index => $result) {
    $insertValues[] = "(:jobId{$index}, :propertyId{$index}, :queued_scan_priority{$index})";
    $params[":jobId{$index}"] = $result['job_id']; 
    $params[":propertyId{$index}"] = $result['occurrence_property_id'];
    $params[":queued_scan_priority{$index}"] = 1;
}
$insertQuery .= implode(', ', $insertValues);
$insertStmt = $pdo->prepare($insertQuery);
$insertStmt->execute($params);

// Add logic to engine that removes $filters once related scans are complete

// Add logic to add to deactivate Rescan button if scan is queued.

