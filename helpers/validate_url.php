<?php
require_once '../init.php';
require '../vendor/autoload.php';

header('Content-Type: application/json');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

function validateUrl($url, $discovery_type, $pdo) {
    // Your existing validation logic remains unchanged

    // Instantiate the client with a more browser-like User-Agent
    $client = new Client([
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'
        ],
        RequestOptions::VERIFY => false // Turn off SSL verification. Use with caution in production.
    ]);

    try {
        $response = $client->request('GET', $url, ['timeout' => 5]);
        
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            return ["valid" => true, "log" => ""];
        } else {
            return ["valid" => false, "log" => "URL is not accessible, Status: " . $response->getStatusCode()];
        }
    } catch (GuzzleException $e) {
        return ["valid" => false, "log" => "URL is not accessible: " . $e->getMessage()];
    }
}
