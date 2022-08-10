<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We get pages using functions in this document.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;


/**
 * Run Curl
 */
function run_curl($site_url, $type = ''){

    // This function creates the following array.
    $output = array(
        'url' => '',
        'content' => ''
    );

    // Setup cURL.
    $curl = curl_init($site_url);
    curl_setopt($curl, CURLOPT_URL, $site_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_PROTOCOLS,
        CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, 
        CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');

    // Restrict cURL to the type of what you want to add.
    if($type == 'wordpress')
        curl_setopt(
            $curl, CURLOPT_HTTPHEADER, 
            array('Accept: application/json')
        );
    if($type == 'xml')
        curl_setopt(
            $curl, CURLOPT_HTTPHEADER,
             array('Accept: application/xml')
        );

    // Execute cURL.
    $output['content'] = curl_exec($curl);

    // Fallback if no contents exist.
    if($output['content'] == false) {
        throw new Exception(
            "Contents of $site_url cannot be loaded"
        );
    }
    curl_close($curl);

    // Let's save the address of the URL we curled so
    // integrations can use it.
    $output['url'] = curl_getinfo(
        $curl, CURLINFO_EFFECTIVE_URL
    );

    // We use the curled URL as the unique ID.
    return $output;

}

/**
 * Single Page Adder
 */
function single_page_adder($site_url){
    // ensure protocol is set; if not, default to https
    $parsed_url = parse_url($site_url);  
    if (empty($parsed_url['scheme'])) {
        $site_url = 'https://' . ltrim($site_url, '/');
    }

    $client = new Client();
    $response = $client->get($site_url);

    // This is primarily a check that the URL is accessible.
    // If site has any content, we return the site URL in an array.
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
    // Instantiate Guzzle client - WP API uses JSON
    $options = ['headers' => ['Accept' => 'application/json']];
    $client = new Client($options);

    // WP API JSON endpoint is always the same
    $wp_json_endpoint = '/wp-json/wp/v2/pages?per_page=100';
    $url = $site_url . $wp_json_endpoint;

    $wp_api_json = json_decode($client->get($url)->getBody(), true);

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
    $options = ['headers' => ['Accept' => 'application/xml']];
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