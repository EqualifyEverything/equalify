<?php
// Add initialization info
require_once('../init.php');

// Start session to securely rescan page.
$page_property_id = $_SESSION['page_property_id'];
$page_id = $_SESSION['page_id'];
$page_url = $_SESSION['page_url'];
if(isset($_SESSION['report_id'])) // Report IDs can be blank
    $report_id = $_SESSION['report_id'];

// Send URL to scan
$api_url = 'http://198.211.98.156/generate/url';
$data = json_encode(
    array(
        "url" => $page_url, 
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
    header("Location: ../index.php?view=page&page_id=$page_id&report_id=$report_id&error=" .urlencode('No response.'));
    exit;
}

// Add results into scan queue.
$stmt = $pdo->prepare("INSERT INTO queued_scans (queued_scan_job_id, queued_scan_property_id, queued_scan_page_id, queued_scan_prioritized) VALUES (:queued_scan_job_id, :queued_scan_property_id, :queued_scan_page_id, :queued_scan_prioritized)");
$stmt->bindParam(':queued_scan_job_id', $response_data['jobID'], PDO::PARAM_INT);
$stmt->bindParam(':queued_scan_property_id', $page_property_id, PDO::PARAM_INT);
$stmt->bindParam(':queued_scan_page_id', $page_id, PDO::PARAM_INT);
$stmt->bindValue(':queued_scan_prioritized', 1, PDO::PARAM_INT);
$stmt->execute();

// Remove session.
$_SESSION['page_id'] = '';

// Set success messsage as session.
$_SESSION['success'] = 'Rescanning page. Refresh for updates.';

// Redirect
header("Location: ../index.php?view=page&page_id=$page_id&report_id=$report_id");
exit;