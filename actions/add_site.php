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
    single_page_adder($site_url);

// WordPress sites are added via their API.
}elseif($type == 'wordpress'){
    wordpress_site_adder($site_url);

// .XML sites use the latest version of XML standards.
}elseif($type == 'XML'){
    xml_site_adder($site_url);

// Since we're passing type through a URL, we have a fallback
// in case someone passes an unsupported 'type'. 
}else{
    throw new Exception('"'.$type.'" sites are unsupported');
}

// Back home we go.
header('Location: ../index.php?view=sites&status=success');