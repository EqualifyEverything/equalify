<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Alerts can be added with this process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// First, we'll log what we're doing.
echo "\nAdding an alert.";


// We require five arguements to run this process (we're
// asking for 8 because PHP adds the request as an arg).
if($argc !== 8)
    throw new Exception('g arguments are required -
    '.$argc.' passed', 1);

// We can scope the arguments to their variables.
$source          = $argv[1];
$url             = $argv[2];
$integration_uri = $argv[3];
$type            = $argv[4];
$status          = $argv[5]; 
$message         = $argv[6];
$meta            = $argv[7];

// Now lets log the arguments.
echo "
    > source: $source
    > url: $url
    > integration: $integration_uri
    > type: $type 
    > status: $status 
    > message: $message
    > meta: $meta
";

// We're going to use the DB.
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');

// Let's validate the variables to make sure they include
// allowed data.
$allowed_sources = array('system', 'page');
if(!in_array($source, $allowed_sources))
    throw new Exception(
        'Alert source, "'.$source.'," is invalid'
    );
$allowed_types = array(
    'error', 'warning', 'notice'
);
if(!in_array($type, $allowed_types))
    throw new Exception(
        'Alert type, "'.$type.'," is not allowed'
    );
$allowed_statuses = array(
    'unread', 'read', 'ignored'
);
if(!in_array($status, $allowed_statuses))
    throw new Exception(
        'Alert status, "'.$status.'," is invalid'
    );

// We should also sanitize the message to a format ready
// for the DB.
$message = htmlspecialchars(
    $message, ENT_NOQUOTES
);
if(is_array($meta)){
    $meta = htmlspecialchars(
        serialize($meta), ENT_NOQUOTES
    );
}

// Let's reformat the arguments so that they can be used
// in our DB calls.
$alert_arguments = array(
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
        'name' => 'status',
        'value'=> $status
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

// Since integrations add alerts when they run, and they 
// can run multiple times, let's see if the same data
// was saved as an alert before.
$existing_alerts = DataAccess::get_db_entries(
    'alerts', $alert_arguments
);
if(!empty($existing_alerts)){

    // All alerts with the same data will need to be
    // updated.
    foreach($existing_alerts as $alert){

        // Updating the alert's status will also update
        // its timestamp. 
        DataAccess::update_db_column_data( 
            'alerts', $alert->id, 'status', 'unread'
        );

    }

}else{

    // Lets add an unread alert, since it doesn't already 
    // exists.
    DataAccess::add_db_entry( 'alerts', $alert_arguments);

}