<?php
include '../db.php'; 

try {

    $queued_sitemap = get_queued_sitemap();

    if(!empty($queued_sitemap[0])){
        
        // We'll generate the sitemap
        $next = $queued_sitemap[0];
        $results = get_sitemap_results($next['queued_sitemap_url']);
        save_to_database($results, $next['queued_sitemap_property_id']);
        echo "Success!".$next['queued_sitemap_url']." processed.\n";   

        // On success, remove sitemap from db.
        remove_sitemap_from_queue($next['queued_sitemap_url']);

    }else{
        echo "No sitemaps to process.\n";
        exit;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Function to get all queued sitemaps
function get_queued_sitemap() {
    global $pdo;
    $query = "SELECT queued_sitemap_url, queued_sitemap_property_id FROM queued_sitemaps ORDER BY queued_sitemap_id ASC";
    $statement = $pdo->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function get_sitemap_results($queued_sitemap_url) {
    // API endpoint
    $api_url = 'http://198.211.98.156/generate/sitemapurl';

    // Prepare the payload
    $data = json_encode(array("url" => $queued_sitemap_url));

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

function remove_sitemap_from_queue($sitemap_url) {
    global $pdo;

    $query = "DELETE FROM queued_sitemaps WHERE queued_sitemap_url = :sitemap_url";
    $statement = $pdo->prepare($query);
    $statement->execute([':sitemap_url' => $sitemap_url]);

    // You can check the number of affected rows if needed
    $deletedRows = $statement->rowCount();

    return $deletedRows; // Returns the number of rows deleted
}
?>