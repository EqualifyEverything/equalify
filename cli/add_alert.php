<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Alerts can be added with this process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// First, we'll log what we're doing.
echo "\n Adding an alert.";

// We're going to use the DB.
require_once 'config.php';
require_once 'models/db.php';

// We require five arguements to run this process.
if($argc !== 6)
    throw new Exception('5 arguments are required', 1);

// We can scope the arguments to their variables.
$source          = $argv[1];
$url             = $argv[2];
$integration_uri = $argv[3];
$type            = $argv[4]; 
$message         = $argv[5];
$meta            = $argv[6];

// Since integrations add alerts when they run, and they 
// can run multiple times, we need to see if the same
// data was saved as an alert before.
$filtered_to_arguments = array(
    array(
        'name' => 'source',
        'value'=> $source
    ),
    array(
        'name' => 'url',
        'value'=> $url
    ),
    array(
        'name' => 'integration_uri',
        'value'=> $integration_uri
    ),
    array(
        'name' => 'type',
        'value'=> $type
    ),
    array(
        'name' => 'message',
        'value'=> $message
    ),
    array(
        'name' => 'meta',
        'value'=> $meta
    ),
);
$existing_alerts = DataAccess::get_alerts(
    $filtered_to_arguments
);
if(!empty($existing_alerts)){

    // We are going to update existing alerts to set 
    // thier timestamp and an "unread" status if the
    // alert does not have an "ignored" status.

}else{

    // Lets add the alert, since it doesn't already
    // exist.

}