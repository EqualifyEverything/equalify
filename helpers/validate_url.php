<?php
require_once '../init.php';
require '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

header('Content-Type: application/json');

// Validation logic
function validateUrl($url, $discovery_type, $pdo) {
    // Check if the URL is valid
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ["valid" => false, "log" => "Invalid URL format"];
    }

    // Check for uniqueness in the database
    $query = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE property_url = :url AND property_discovery = :discovery_type");
    $query->execute([':url' => $url, ':discovery_type' => $discovery_type]);
    $count = $query->fetchColumn();

    if ($count > 0) {
        return ["valid" => false, "log" => "Property already exists."];
    }

    // Check if the URL is accessible
    $client = new Client();
    try {
        $response = $client->request('GET', $url, ['timeout' => 5]); // 5 seconds timeout
        
        // You might want to check for specific status codes here depending on your requirements
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            return ["valid" => true, "log" => ""];
        } else {
            return ["valid" => false, "log" => "URL is not accessible"];
        }
    } catch (GuzzleException $e) {
        // This catches errors like connection timeouts, DNS errors, etc.
        return ["valid" => false, "log" => "URL is not accessible: " . $e->getMessage()];
    }
}