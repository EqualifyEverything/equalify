<?php

// Let's load in Composer.
require_once ('/var/www/html/vendor/autoload.php');

// Let's run Guzzle.
use GuzzleHttp\Client;

// Create a client with a base URI
$client = new GuzzleHttp\Client();

// Send a request
//$response = $client->request('GET', 'https://wave.webaim.org/api/docs?format=json');
$response = $client->request('GET', 'https://axe.equalify.app/index.php?url=decubing.com');

$axe_json = $response->getBody()->getContents();
$axe_json_decoded = json_decode($axe_json);

$page_url = 'test.com';

// START PLUGIN

// Decode JSON.
$axe_json_decoded = json_decode($axe_json);

// Sometimes Axe can't read the json.
if(empty($axe_json_decoded)){

    // And add an alert.
    $alert = array(
        'source'  => 'axe-core',
        'url'     => $page_url,
        'message' => 'axe-core cannot reach the page.',
    );
    array_push($axe_alerts, $alert);

}else{

    // We're add a lit of violations.
    $axe_violations = array();

    // Show axe violations
    foreach($axe_json_decoded[0]->violations as $violation){

        // Only show violations.
        $axe_violations[] = $violation;

    }

    // Add alerts.
    if(!empty($axe_violations)) {

        // Setup alert variables.
        foreach($axe_violations as $violation){

            // Default variables.
            $alert = array();
            $alert['source'] = 'axe-core';
            $alert['url'] = $page_url;

            // Setup tags.
            $alert['tags'] = $violation->tags;

            // Setup message.
            $alert['message'] = '"'.$violation->id.'" violation: '.$violation->help;

            // Push alert.
            $axe_alerts[] = $alert;
            
        }

    }

}

// Test to make sure it works
echo '<pre>';
print_r($axe_alerts);
echo '</pre>';
die;

// Return everything
// return $axe_alerts;