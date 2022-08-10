<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We get pages using functions in this document.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Lets get Composer dependencies.
require_once '../vendor/autoload.php';
use GuzzleHttp\Client;

/**
 * Single Page Adder
 */
function single_page_adder($site_url){

    // Ensure protocol is set; if not, default to https.
    $parsed_url = parse_url($site_url);  
    if (empty($parsed_url['scheme'])) {
        $site_url = 'https://' . ltrim($site_url, '/');
    }

    $options = ['verify' => false];
    $client = new Client($options);
    $response = $client->get($site_url);

    // This is primarily a check that the URL is 
    // accessible. If site has any content, we return the 
    // site URL in an array.
    if ($response->getBody()) {
        return [$site_url];
    } else {
        throw new Exception("$site_url returned no content");
    }

}

/**
 * WordPress Pages Adder
 */
function wordpress_site_adder($site_url){
    
    // Instantiate Guzzle client - WP API uses JSON.
    $options = [
        'headers' => ['Accept' => 'application/json'],
        'verify' => false,
    ];
    $client = new Client($options);

    // The WP API JSON endpoint is always the same.
    $wp_json_endpoint = '/wp-json/wp/v2/pages?per_page=100';
    $url = $site_url . $wp_json_endpoint;

    $wp_api_json = json_decode(
        $client->get($url)->getBody(), true
    );

    if(empty($wp_api_json[0])) {
        throw new Exception(
            "$site_url does not include WordPress " .
            "functionality that Equalify requires"
        );
    }

    // Push JSON to pages array.
    $pages = [];
    foreach ($wp_api_json as $page):
        array_push($pages, $page['link']);
    endforeach;

    // We want an array with each page URL.
    return $pages;    
    
}

/**
 * XML Site Adder
 */
function xml_site_adder($site_url){
    
    // Instantiate Guzzle client to accept XML
    $options = [
        'headers' => ['Accept' => 'application/xml'],
        'verify' => false,
    ];
    $client = new Client($options);

    // Valid XML files are only allowed!
    $xml_contents = $client->get($site_url)->getBody();
    if(!str_starts_with($xml_contents, '<?xml')) {
        throw new Exception("$site_url did not return XML");
    }

    // Convert XML to JSON, so we can use it later
    $xml = simplexml_load_string($xml_contents);
    $json = json_encode($xml);
    $json_entries = json_decode($json, true);

    // Push JSON to pages array.
    $pages = [];
    foreach ($json_entries['url'] as $page):
        array_push($pages, $page['loc']);
    endforeach;

    // Prepare contents and return them.
    return $pages;

}