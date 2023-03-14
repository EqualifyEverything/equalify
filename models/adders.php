<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We get pages using functions in this document.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// Let's load in Composer.
require_once (__ROOT__.'/vendor/autoload.php');

// Let's run Guzzle.
use GuzzleHttp\Client;
use Psr\Http\Message\MessageInterface;

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
    // accessible. If the site has any content, we return the 
    // site URL in an array.
    if ($response->getBody()) {
        return [$site_url];
    } else {
        throw new Exception("$site_url returned no content");
    }

}

// handle updating alert records with results.
function processItem($item)
{
    print_r($item);
}

/**
 * A11yWatch Crawler Pages Adder
 */
function a11ywatch_site_adder($site_url){
    $jwt = DataAccess::get_meta_value('a11ywatch_key');

    // Instantiate Guzzle client - A11yWatch API uses JSON streams.
    $options = [
        'headers' => ['Content-Type' => 'application/json', 'Transfer-Encoding' => 'chunked', 'Authorization' => $jwt],
        'verify' => false,
        'base_uri' => $GLOBALS['a11ywatch_uri'],
    ];

    $client = new Client($options);

    $response = $client->request("POST", '/api/crawl', [
        GuzzleHttp\RequestOptions::JSON => [ 'url' => $site_url ]
    ]);
    $parser = new \JsonCollectionParser\Parser();
    $parser->parseAsObjects($response->getBody(), 'processItem');

    // // Push JSON to pages array.
    // $pages = [];
    // foreach ($a11ywatch_api_json as $page):
    //     array_push($pages, $page['link']);
    // endforeach;

    // We want an array with each page URL.
    return [];    
    
}
