<?php
// Initialization is designed to be done from command line
// so we can do things like trigger via CRON.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/init.php'); 

// Helpers
require_once(__ROOT__.'/helpers/get_next_scannable_property.php');
require_once(__ROOT__.'/helpers/get_property.php');

try {

    // Check if a property is defined
    if(isset($_SESSION['property_id'])){
        $next_property_id = $_SESSION['property_id']; // Define property to scan by setting session.
        $next_property_url = get_property($next_property_id)['property_url'];
    }else{
        $next_property = get_next_scannable_property();
        $next_property_id = $next_property['property_id'];
        $next_property_url = $next_property['property_url'];
    }

    if(!empty($next_property_id)){

        // Mark the scan as running
        update_property_processing_data($next_property_id, 1);

        $results = get_api_results($next_property_url);

        if(results_are_valid_format($results) == TRUE)
            save_to_database($results, $next_property_id);

        // On success
        update_property_processing_data($next_property_id, NULL);
        echo "Success! $next_property_url processed.\n";   

    }else{

        // Kill scan if no properties to scan.
        echo "No properties to process.\n";
        
    }

    // Clear Session Variable
    if(isset($_SESSION['property_id']))
        unset($_SESSION['property_id']);

} catch (Exception $e) {

    // Clear out scanning data and post error
    update_property_processing_data($next_property_id, NULL);
    echo date('Y-m-d H:i:s').': ' . $e->getMessage();
    if(isset($_SESSION['property_id']))
        unset($_SESSION['property_id']);
    exit;

}

function get_api_results($property_url) {
    
    // Set API endpoint
    $api_url = $_ENV['SCAN_URL'].'/generate/sitemapurl';

    // Prepare the payload
    $data = json_encode(array("url" => $property_url));

    // Initialize cURL session
    $ch = curl_init($api_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));

    // Execute cURL session
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)){
        throw new Exception(curl_error($ch));
    }

    // Close cURL session
    curl_close($ch);

    // Decode JSON response
    $results = json_decode($response, true);

    return $results;
}


function update_property_processing_data($property_id, $property_processing = NULL) {
    global $pdo;
    $current_date_time = date('Y-m-d H:i:s');

    $update_query = "
        UPDATE properties 
        SET 
            property_processing = :property_processing, 
            property_processed = :property_processed
        WHERE 
            property_id = :property_id
    ";

    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute([
        ':property_processing' => $property_processing, 
        ':property_processed' => $current_date_time, // Set the current date and time
        ':property_id' => $property_id
    ]);
}

function results_are_valid_format($results) {

    // First check if JSON decoding was successful and is an array
    if ($results === null || !is_array($results)) {
        throw new Exception("Property results are not formatted correctly");
    }

    // Validate each element in the array
    foreach ($results as $item) {
        if (!isset($item['JobID']) || !isset($item['URL'])) {
            throw new Exception("$item");
        }
    }

    // On sucesss
    return true;

}

function save_to_database($results, $property_id) {
    global $pdo;

    // Extract all job IDs from the results
    $jobIds = array_column($results, 'JobID');

    // Check which job IDs already exist in the database
    $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
    $checkQuery = "SELECT queued_scan_job_id FROM queued_scans WHERE queued_scan_job_id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute($jobIds);
    $existingJobIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Filter out existing job IDs from results
    $newResults = array_filter($results, function($result) use ($existingJobIds) {
        return !in_array($result['JobID'], $existingJobIds);
    });

    // Batch insert the new results
    if (!empty($newResults)) {
        $insertQuery = "INSERT INTO queued_scans (queued_scan_job_id, queued_scan_property_id) VALUES ";
        $insertValues = [];
        $params = [];
        foreach ($newResults as $index => $result) {
            $insertValues[] = "(:jobId{$index}, :propertyId{$index})";
            $params[":jobId{$index}"] = $result['JobID'];
            $params[":propertyId{$index}"] = $property_id;
        }

        $insertQuery .= implode(', ', $insertValues);
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute($params);
    }
}
?>