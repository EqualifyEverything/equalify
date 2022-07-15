<?php
/*****************************************************/

// For testing purposed, let's make up an array of 
// queued alerts. By the end of this,
$queued_alerts = array(


);
$process_info = array(
    'sources'  => array(
        'new source', 'source 2', 'existing source'
    ),
    'urls'     => array(
        'newurl.com', 'existingurl2.com', 'newurl2.com'
    )
);

// We'll use the process alert helper
require_once('helpers/process_alerts.php');

process_alerts($queued_alerts, $process_info);
