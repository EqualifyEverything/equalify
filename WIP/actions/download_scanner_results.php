<?php

// Function to send a GET request
function sendGetRequest($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Define the path to the response.json file
$jsonFilePath = "/var/www/html/WIP/_temp/response.json"; // Use the correct file path

// Check if the file exists and is readable
if (!file_exists($jsonFilePath) || !is_readable($jsonFilePath)) {
    die("Error: Unable to locate or read the file at '$jsonFilePath'.\n");
}

// Read the response.json file
$jsonData = file_get_contents($jsonFilePath);
$data = json_decode($jsonData, true);

// Directory to save downloaded files
$saveDir = '../_temp/';

// Loop through each job ID
foreach ($data as $job) {
    $jobID = $job['JobID']; // Correct key for job ID
    $filePath = $saveDir . $jobID . ".json";

    // Check if the file for this job ID already exists
    if (file_exists($filePath)) {
        echo "File for JobID $jobID already exists, skipping download.\n";
        continue;
    }

    $apiUrl = "http://198.211.98.156/results/" . $jobID;

    // Query the API for each job
    $result = sendGetRequest($apiUrl);
    $resultData = json_decode($result, true);

    // Check if the job is completed
    if (isset($resultData['status']) && $resultData['status'] === 'completed') {
        // Save the file with job ID as the filename
        file_put_contents($filePath, $result);
        echo "Downloaded and saved file for JobID $jobID.\n";
    } else {
        echo "JobID $jobID is not completed yet, skipping.\n";
    }
}

echo "Process completed for all jobs.\n";

?>