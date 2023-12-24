<?php
include '../db.php'; 

try {

    $next = get_next_property_to_process();

    if(!empty($next)){

        // Mark the scan as running
        update_property_processing_data($next['property_id'], 1);
        
        // We'll generate the property
        $results = get_property_results($next['property_url'], $next['property_crawl_type']);

        if(results_are_valid_format($results) == TRUE)
            save_to_database($results, $next['property_id']);

        // On success, 
        update_property_processing_data($next['property_id'], NULL);
        echo "Success! Property ".$next['property_id']." processed.\n";   
        exit;        

    }else{
        echo "No properties to process.\n";
        exit;
    }

} catch (Exception $e) {

    // Clear out processing data and post error
    update_property_processing_data($next['property_id'], NULL);
    echo 'Error: ' . $e->getMessage();

}

function get_next_property_to_process() {
    global $pdo;
    $query = "
        SELECT 
        property_id, 
        property_crawl_type, 
        property_url 
    FROM properties
    WHERE 
        (property_archived != 1 OR property_archived IS NULL) AND
        (property_processing != 1 OR property_processing IS NULL) AND
        (property_processed IS NULL OR property_processed <= DATE_SUB(NOW(), INTERVAL 1 DAY)) AND
        NOT EXISTS (
            SELECT 1 FROM properties WHERE property_processing = 1
        )
    ORDER BY property_processed ASC
    LIMIT 1;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
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

function get_property_results($property_url, $property_crawl_type) {
    
    // Set API endpoint
    if($property_crawl_type == 'sitemap'){
        $api_url = 'http://198.211.98.156/generate/sitemapurl';
    }elseif($property_crawl_type == 'single_page'){
        $api_url = 'http://198.211.98.156/generate/url';
    }else{
        throw new Exception("'$property_crawl_type' is not a valied crawl type");
    }

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

function results_are_valid_format($results) {

    // First heck if JSON decoding was successful and is an array
    if ($results === null || !is_array($results)) {
        throw new Exception("Results are not formatted correctly");
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