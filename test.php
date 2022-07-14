<?php
/*****************************************************/

// For testing purposed, let's make up an array of 
// queued alerts. By the end of this,
$queued_alerts = array(
    array(
        'source'  => 'existing source',
        'url'     => 'newurl.com',
        'type'    => 'duplicate',
        'status'  => 'duplicate',
        'message' => 'duplicate',
        'meta'    => 'duplicate'
    ),
    array(
        'source'  => 'existing source',
        'url'     => 'existingurl.com',
        'type'    => 'new',
        'status'  => 'new',
        'message' => 'new',
        'meta'    => 'new'
    ),
    array(
        'source'  => 'new source',
        'url'     => 'existingurl2.com',
        'type'    => 'new',
        'status'  => 'new',
        'message' => 'new',
        'meta'    => 'new'
    )
);


// We'll use the process alert helper
require_once('helpers/process_alerts.php');

process_alerts($queued_alerts);
