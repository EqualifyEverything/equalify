<?php
require_once '../init.php';
require '../vendor/autoload.php';

header('Content-Type: application/json');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

function validateUrl($url) {
    // Instantiate the client with a proper User-Agent
    $client = new Client([
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (compatible; EqualifySiteValidator/0.0.1; ' . $url . ')'
        ],
        RequestOptions::VERIFY => false // Turn off SSL verification - this is for staging purposes.
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