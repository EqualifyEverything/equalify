<?php

// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';

// We don't validate the URLs here because cURL does
// a better job of validating/redirecting in the adders.
$site_url = $_GET['url'];
if($site_url == false)
    throw new Exception('URL is missing');

// We need to check the type since a user could manually
// update the URL string to something unsupported.
$type = $_GET['type'];
if( $type == false)
    throw new Exception('Type is not specified for the URL "'.$site_url.'"');

// Static pages are treated as sites in themselves.
if($type == 'single_page' ){

    // The adder cURLs the site to test if the URL can be scanned.
    $curled_site = single_page_adder($site_url);

    // Site URL changes to the curled URL.
    $site_url = $curled_site['url'];

    // Single pages are saved with the following pramenters
    $type = 'single_page';
    $status = 'active';
    $site = $curled_site['url'];
    $is_parent = 1;
    DataAccess::add_page($site_url, $type, $status, $site, $is_parent );
    
// WordPress and XML deals with adding pages similarly,
// so their functions are wrapped in one condition.
}elseif($type == 'wordpress' || $type == 'xml' ){

    // WordPress API is queried to create sites.
    if($type == 'wordpress' )
        $curled_site = wordpress_site_adder($site_url);

    // .XML adder can create lots of pages.
    if($type == 'xml' )
        $curled_site = xml_site_adder($site_url);

    // Both XML and WP deliver similar content.
    $pages = $curled_site['contents'];
    $site_url = $curled_site['url'];

    // We're setting the status and adding pages here so we
    // do not have to call the db inside "models/adders.php",
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
    DataAccess::add_pages($pages_records);

// Since we're passing type through a URL, we have a fallback
// in case someone passes an unsupported 'type'. 
}else{
    die('"'.$type.'" sites are unsupported.');
}

// Back home we go.
header('Location: ../index.php?view=sites&status=success');