<?php


// We're going to use the DB in this document.
require_once 'config.php';
require_once 'models/db.php';

// Let's test how an alert is posted.
$attributes = array(
    'source' => 'system', 
    'url' => 'test.com', 
    'type' => 'error', 
    'message' => 'this is only a test', 
    'meta' => array('hello')
);
DataAccess::add_alert($attributes);

?>
