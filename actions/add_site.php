<?php

// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Valid URLs are required so that we can CURL.
$site_url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($site_url == false)
    die('URL"'.$_GET['url'].'" format is invalid or missing.');

// Different types require different scans.
$type = $_GET['type'];
if( $type == false)
    die('Page type is not specified for the URL "'.$site_url.'".');

// Requiring unique URLS minimizes unnessary scans.
if(!is_unique_page_url($db, $site_url))
    die('Page "'.$site_url.'" already exists.');

// Static pages are treated as sites in themselves.
if($type == 'single_page' ){
    add_page($db, $url = $site_url, $type, 'active', $site = $site_url, $is_parent = 1 );

// WordPress and XML deal with adding pages similarly,
// so their functions are wrapped in one condition.
}elseif($type == 'wordpress' || $type == 'xml' ){

    // WordPress API is queried to create sites.
    if($type == 'wordpress' ){

        // Lots of users don't include backslashes,
        // which WordPress requirew to access the API.
        if( !str_ends_with($site_url, '/') )
            $site_url = $site_url.'/';

        // WordPress adder can create lots of pages.
        $pages = wordpress_site_adder($site_url);

    }

    // .XML adder can create lots of pages.
    if($type == 'xml' )
        $pages = xml_site_adder($site_url);

    // We're setting the status and adding pages here so we
    // do not have to call the $db inside "models/adders.php",
    // keeping each model focused on distinct functions.
    $pages_records = [];
    foreach ($pages as &$page):

        // Though these variables were set, we must reset them for
        // one page, since we have now generated many pages.
        if($site_url == $page['url']){
            $is_parent = 1;

            // This will be used later..
            $has_parent = true;

        }else{
            $is_parent = '';
        }

        // Push each page to pages' records.
        array_push(
            $pages_records, 
            array(
                'url'       => $page['url'], 
                'site'      => $site_url,
                'is_parent' => $is_parent,
                'status'    => 'active',
                'type'      => $type
            )
        );

    endforeach; 

    // Some newly created record arrays do not have existing sites
    // and do not contain a parent because API/XML records contain 
    // different URLs than the URL where the API/XML exists. In that 
    // case, the first record, which is often the homepage, becomes 
    // the parent.
    if(!isset($has_parent) && !isset($existing_site)){
        $first_record = &$pages_records[0];
        $first_record['is_parent'] = 1;
    }

    // Finalllly, we can add pages to the DB.
    add_pages($db, $pages_records);

// Since we're passing type through a URL, we have a fallback
// in case someone passes an unsupported 'type'. 
}else{
    die('"'.$type.'" sites are unsupported.');
}

// Back home we go.
header('Location: ../index.php?view=sites&status=success');