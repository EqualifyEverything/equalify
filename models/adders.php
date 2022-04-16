<?php
/**
 * Get Page Body
 */
function get_url_contents($url){
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');
    $url_contents = curl_exec($curl);
    if($url_contents == false)
        throw new Exception('Contents of "'.$url.'" cannot be loaded');
    curl_close($curl);
    return $url_contents;
}

/**
 * Single Page Adder
 */
function single_page_adder($site_url){

    // Reformat URL for JSON request.
    $json_url = $site_url.'wp-json/wp/v2/pages?per_page=100';

    // Get URL contents so we can make sure URL
    // can be scanned.
    $url_contents = get_url_contents($site_url);
    echo $url_contents;
    die;
}

/**
 * WordPress Pages Adder
 */
function wordpress_site_adder($site_url){

    // Reformat URL for JSON request.
    $json_url = $site_url.'wp-json/wp/v2/pages?per_page=100';

    // Get URL contents.
    $url_contents = get_url_contents($site_url);

    // Create JSON.
    $wp_api_json = json_decode($url_contents, true);
    if(empty($wp_api_json[0]))
        throw new Exception('The URL "'.$site_url.'" does not contain valid WordPress API V2 JSON.');

    // Push JSON to pages array.
    $pages = [];
    foreach ($wp_api_json as $page):
        array_push($pages, array('url' => $page['link']));
    endforeach;
    return $pages;
}

/**
 * XML Site Adder
 */
function xml_site_adder($site_url){

    // Reformat URL for XML request.
    $xml_url = $site_url;

    // Get URL contents.
    $url_content = get_url_contents($site_url);

    // Valid XML files are only allowed!
    if(!str_starts_with($url_contents, '<?xml'))
        throw new Exception('"'.$site_url.'" is not valid XML');

    // Convert XML to JSON, so we can use it later
    $xml = simplexml_load_string($url_contents);
    $json = json_encode($xml);
    $json_entries = json_decode($json,TRUE);

    // Push JSON to pages array.
    $pages = [];
    foreach ($json_entries['url'] as $page):
        array_push($pages, array('url' => $page['loc']));
    endforeach;
    return $pages;

}