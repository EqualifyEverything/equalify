<?php

/**
 * Get Page Body
 */
function run_curl($site_url, $type = ''){
    $curl = curl_init($site_url);
    curl_setopt($curl, CURLOPT_URL, $site_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');

    // Restrict CURL to the type of what you want to add.
    if($type == 'wordpress')
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    if($type == 'xml')
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/xml'));

    // Execute CURL
    $url_contents = curl_exec($curl);

    // Add in DB info so we can see if URL is unique.
    require_once '../config.php';
    require_once 'db.php';
    $db = connect(
        DB_HOST, 
        DB_USERNAME,
        DB_PASSWORD,
        DB_NAME
    );

    // The curled URL is the URL we use as an ID.
    $curled_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

    // We don't include added enpoints to the URL.
    $json_endpoints = '/wp-json/wp/v2/pages?per_page=100';
    $curled_url = str_replace($json_endpoints, '', $curled_url);

    // Make sure URL is unique to minimize scans. 
    if(!is_unique_site($db, $curled_url))
        throw new Exception('"'.$curled_url.'" already exists');

    // Fallback if no contents exist.
    if($url_contents == false)
        throw new Exception('Contents of "'.$curled_url.'" cannot be loaded');
    curl_close($curl);

    // We use the curled URL as the unique ID.
    return array(
        'url' => $curled_url,
        'contents' => $url_contents
    );

}

/**
 * Single Page Adder
 */
function single_page_adder($site_url){

    // Get URL contents so we can make sure URL
    // can be scanned.
    return run_curl($site_url);

}

/**
 * WordPress Pages Adder
 */
function wordpress_site_adder($site_url){

    // Add WP JSON URL endpoints for request.
    $json_endpoints = '/wp-json/wp/v2/pages?per_page=100';
    $json_url = $site_url.$json_endpoints;

    // Get URL contents.
    $curled_site = run_curl($json_url, 'wordpress');

    // Create JSON.
    $wp_api_json = json_decode($curled_site['contents'], true);
    if(empty($wp_api_json[0]))
        throw new Exception('The URL "'.$site_url.'" is not valid output');

    // Push JSON to pages array.
    $pages = [];
    foreach ($wp_api_json as $page):
        array_push($pages, array('url' => $page['link']));
    endforeach;

    // Remove WP JSON endbpoints.
    $clean_curled_url = str_replace($json_endpoints, '', $curled_site['url']);

    // Reformat the curled contents to be an array we can 
    // work with.
    return array(
        'url' => $clean_curled_url,
        'contents' => $pages
    );

}

/**
 * XML Site Adder
 */
function xml_site_adder($site_url){

    // Get URL contents.
    $curled_site = run_curl($site_url, 'xml');

    // Valid XML files are only allowed!
    $xml_contents = $curled_site['contents'];
    if(!str_starts_with($xml_contents, '<?xml'))
        throw new Exception('"'.$curled_site['url'].'" is not a readable XML format');

    // Convert XML to JSON, so we can use it later
    $xml = simplexml_load_string($xml_contents);
    $json = json_encode($xml);
    $json_entries = json_decode($json,TRUE);

    // Push JSON to pages array.
    $pages = [];
    foreach ($json_entries['url'] as $page):
        array_push($pages, array('url' => $page['loc']));
    endforeach;

    // Reformat the curled contents to be an array we can 
    // work with.
    return array(
        'url' => $curled_site['url'],
        'contents' => $pages
    );
    
}