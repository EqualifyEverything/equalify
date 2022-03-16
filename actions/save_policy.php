<?php
// Add Dependencies
require_once '../config.php';
require_once '../models/db.php';

// Setup DB Connection
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL Variables and fallbacks
$name = $_GET['name'];
if($name == false)
    throw new Exception('Policy name is missing.');
$action = $_GET['action'];
if($action == false)
    throw new Exception('Policy action is missing.');
$event = $_GET['event'];
if($event == false)
    throw new Exception('Policy event is missing.');
$tested = $_GET['tested'];
if($tested == false){
    throw new Exception('Policy test time is missing.');
}
$frequency = $_GET['frequency'];
if($frequency == false)
    throw new Exception('Policy test frequency is missing.');

// Create Policy Record
$site_record = [
    'name' => $name,
    'action' => $action,
    'event' => $event,
    'tested' => $tested,
    'frequency' => $frequency
];

// Condutionally Update or Save New Policy
if(!empty($_GET['id'])){
    update_policy($db, $site_record, $_GET['id']);
}else{
    insert_policy($db, $site_record);
}
// Redirect
header("Location: ../index.php?view=policies&status=success");