<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We get pages using functions in this document.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/
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
    if($output['content'] == false)
        throw new Exception(
            'Contents of "'.$curled_url.'" cannot be loaded'
        );
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

    // We just run cURL to make sure the URL can be accessed
    // before we run the scan
    $curled_site = run_curl($site_url);

    // If cURL works, we can return the site URL.
    return array($curled_site['url']);

}

/**
 * WordPress Pages Adder
 */
function wordpress_site_adder($site_url){

    // Add WP JSON URL endpoints for request.
    $json_endpoints = '/wp-json/wp/v2/pages?per_page=100';
    $json_url = $site_url.$json_endpoints;

    // Get URL contents.
    $curled_site = run_curl(
        $json_url, 'wordpress'
    )['content'];

    // Create JSON.
    $wp_api_json = json_decode($curled_site, true);
    if(empty($wp_api_json[0]))
        throw new Exception(
            '"'.$site_url.'" does not include WordPress
            functionality that Equalify requires'
        );

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

    // Get URL contents.
    $curled_site = run_curl(
        $site_url, 'xml'
    )['content'];

    // Valid XML files are only allowed!
    $xml_contents = $curled_site;
    if(!str_starts_with($xml_contents, '<?xml'))
        throw new Exception(
            '"'.$curled_site['url'].'" is not a readable 
            XML format'
        );

    // Convert XML to JSON, so we can use it later
    $xml = simplexml_load_string($xml_contents);
    $json = json_encode($xml);
    $json_entries = json_decode($json, TRUE);

    // Push JSON to pages array.
    $pages = [];
    foreach ($json_entries['url'] as $page):
        array_push($pages, $page['loc']);
    endforeach;

    // Prepare contents and return them.
    return $pages;
    
}