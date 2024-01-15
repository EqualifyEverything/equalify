<?php
// Add initialization info
require_once('../init.php');

// Report Id should be passed to page
$report_id = $_GET['report_id'];

// Start session to securely rescan page.
session_start();
$page_id = $_SESSION['page_id'];

// Get Get URLs
    $stmt = $pdo->prepare("
    SELECT
        page_url, page_property_id
    FROM 
        pages     
    WHERE
        page_id = :page_id
");
$stmt->bindParam(':page_id', $page_id, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetch(PDO::FETCH_ASSOC);

// Kill process if there's nothing to scan.
if (empty($results)){
    header("Location: ../index.php?view=page&page_id=$page_id&report_id=$report_id&error=" .urlencode('There are no pages to scan.'));
    exit;
}

// Send URL to scan
$api_url = 'http://198.211.98.156/generate/url';
$data = json_encode(
    array(
        "url" => $results['page_url'], 
        "priortized" => true
    )
);  
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
if (!isset($response_data['jobID'])){
    header("Location: ../index.php?view=page&report_id=$report_id&page_id=$page_id&error=" .urlencode('No response.'));
    exit;
}

// Add results into scan queue.
$stmt = $pdo->prepare("INSERT INTO queued_scans (queued_scan_job_id, queued_scan_property_id, queued_scan_prioritized) VALUES (:queued_scan_job_id, :queued_scan_property_id, :queued_scan_prioritized)");
$stmt->bindParam(':queued_scan_job_id', $response_data['jobID'], PDO::PARAM_INT);
$stmt->bindParam(':queued_scan_property_id', $results['page_property_id'], PDO::PARAM_STR);
$stmt->bindValue(':queued_scan_prioritized', 1, PDO::PARAM_INT);
$stmt->execute();

// Remove session.
$_SESSION['page_id'] = '';

// Redirect
header("Location: ../index.php?view=page&page_id=$page_id&report_id=$report_id&success=" .urlencode('Rescanning page.'));
exit;