<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/scanner.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Set $properties_ids variable
$property_ids = get_property_ids($db);

// Make sure they have enough credits
$credits = get_account($db, USER_ID)->credits;
$properties_count = count($property_ids);
if($credits < $properties_count)
    throw new Exception('Your user, "'.USER_ID.'," has '.$credits.' credits. You need '.$properties_count.' to scan');

// Create scan if no other scans are running.
// TODO: Allow multiple scans
$filters = [
    array(
        'name'  => 'status',
        'value' => 'running'
    ),
];
if( count(get_scans($db, $filters)) == 0 ){
    add_scan($db, 'running', $property_ids);
}else{
    throw new Exception('Only one scan can run at a time');
}

// Load in integrations
$uploaded_integrations = uploaded_integrations();
foreach($uploaded_integrations as $uploaded_integration){
    require_once '../integrations/'.$uploaded_integration['uri'].'/'.$uploaded_integration['uri'].'.php';
}

// Scan each property.
$properties = get_properties($db);
foreach ($properties as $property){

    // Scan each property.
    foreach($uploaded_integrations as $uploaded_integration){
        $uploaded_integration['uri']($uploaded_integration['uri']);
    }
    
}

// Add Scan Record
update_scan_status($db, 'running', 'complete');
die;

// Redirect
header("Location: ../index.php?view=scans&status=success");