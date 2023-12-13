<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document adds a site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Info on DB must be declared to use db.php models.
require_once '../init.php';
require_once '../models/adders.php';
require_once '../models/db.php';

// We don't validate the URLs here because cURL does a better
// job of validating/redirecting in the adders.
$url = $_POST['url'];
if ($url == false) {
    throw new Exception('URL is missing');
}

// We need to check the type since a user could manually
// update the URL string to something unsupported.
$type = $_POST['type'];
if ($type == false) {
    throw new Exception(
        'Type is not specified for the URL "'.$url.'"'
    );
}

// We no longer do different validation for different site types
$site = single_page_adder($url);

// If no errors occur, we can add these profiles into the URL
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
    'scan_profiles', $fields
);

// Back home we go.
header('Location: ../index.php?view=scan_profiles&status=success');