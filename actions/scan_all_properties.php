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
print_r($property_ids);

// Make sure there are properties to scan.
if($property_ids == NULL)
    throw new Exception('You have no active properties to scan');

// Make sure they have enough usage.
$usage = get_account($db, USER_ID)->usage;
$properties_count = count($property_ids);
if($usage < $properties_count)
    throw new Exception('Your user, "'.USER_ID.'," has '.$usage.' usage. You need '.$properties_count.' to scan');

// Create scan if no other scans are running.
// TODO: Allow multiple scans.
$filters = [
    array(
        'name'  => 'status',
        'value' => 'running'
    ),
];
if( count(get_scans($db, $filters)) == 0 ){
    add_scan($db, 'running-test', $property_ids);
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

    // Some integrations use account info.
    $account = get_account($db, USER_ID);

    // Run integration scans.
    foreach($uploaded_integrations as $uploaded_integration){
        $integration_scan_function_name = $uploaded_integration['uri'].'_scans';
        if(function_exists($integration_scan_function_name))
            $integration_scan_function_name($property, $account);
    }

    // Update scanned timestamp.
    update_property_scanned_time($db, $property->id);
    
}

// Subtract account usage.
add_account_usage($db, USER_ID, $properties_count);

// Add scan record.
update_scan_status($db, 'running', 'complete');

// Redirect
header("Location: ../index.php?view=scans&status=success");