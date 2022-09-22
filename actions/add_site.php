<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document adds a site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';

// We don't validate the URLs here because cURL does a better
// job of validating/redirecting in the adders.
$url = $_POST['url'];
if($url == false)
    throw new Exception('URL is missing');

// We need to check the type since a user could manually
// update the URL string to something unsupported.
$type = $_POST['type'];
if( $type == false)
    throw new Exception(
        'Type is not specified for the URL "'.$url.'"'
    );

// We also need to see if the site is of a unique URL.
$filters = array(
    array(
        'name'  => 'url',
        'value' => $url,
        'condition' => 'OR'
    ),
    array(
        'name'  => 'url',
        'value' => $url.'/',
        'condition' => 'OR'
    ),
    array(
        'name'  => 'url',
        'value' => 'https://'.$url.'/',
        'condition' => 'OR'
    ),
    array(
        'name'  => 'url',
        'value' => 'http://'.$url.'/',
        'condition' => 'OR'
    )
);
$sites_with_url = DataAccess::get_db_rows('sites', $filters)['content'];
if( !empty($sites_with_url) )
    throw new Exception('"'.$url.'" already exists');

// Static pages are treated as sites in themselves.
if($type == 'single_page' ){
    $site = single_page_adder($url);

// WordPress sites are added via their API.
}elseif($type == 'wordpress'){
    $site = wordpress_site_adder($url);

// .XML sites use the latest version of XML standards.
}elseif($type == 'xml'){
    $site = xml_site_adder($url);

// Since we're passing type through a URL, we have a fallback
// in case someone passes an unsupported 'type'. 
}else{
    throw new Exception('"'.$type.'" sites are unsupported');
}

// If no errors occur, we can add these sites into the URL
// with several default items.
$fields = array(
    array(
        'name' => 'url',
        'value' => $url
    ),
    array(
        'name' => 'type',
        'value' => $type
    ),
    array(
        'name' => 'status',
        'value' => 'active'
    )
);
DataAccess::add_db_entry(
    'sites', $fields
);

// Back home we go.
header('Location: ../index.php?view=sites&status=success');