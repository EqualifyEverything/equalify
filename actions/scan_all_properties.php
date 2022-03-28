<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/integrations.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Set $properties_ids variable
$property_filters = [
    array(
        'name'  => 'status',
        'value' => 'active'
    ),
];
$property_ids = get_property_ids($db, $property_filters);

// Make sure there are properties to scan.
if($property_ids == NULL)
    throw new Exception('You have no active properties to scan');

// Make sure they have enough credits.
$credits = get_account($db, USER_ID)->credits;
$properties_count = count($property_ids);
if($credits < $properties_count)
    throw new Exception('Your user, "'.USER_ID.'," has '.$credits.' credits. You need '.$properties_count.' to scan');

// Create scan if no other scans are running.
// TODO: Allow multiple scans.
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

// Load integrations.
$uploaded_integrations = uploaded_integrations('../integrations');
foreach($uploaded_integrations as $uploaded_integration){
    require_once '../integrations/'.$uploaded_integration['uri'].'/functions.php';
}

// Scan each active property.
$property_filters = [
    array(
        'name'  => 'status',
        'value' => 'active'
    ),
];
$properties = get_properties($db, $property_filters);
foreach ($properties as $property){

    // Run Integrations
    foreach($uploaded_integrations as $uploaded_integration){
        $uploaded_integration['uri']($property->url);
    }

    // Update scanned timestamp.
    update_property_scanned_time($db, $property->id);
    
}

// Subtract account credits.
subtract_account_credits($db, USER_ID, $properties_count);

// Add scan record.
update_scan_status($db, 'running', 'complete');

// Redirect
header("Location: ../index.php?view=scans&status=success");