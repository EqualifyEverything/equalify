<?php

// Add DB info and required functions.
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/integrations.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Setup queries to minize db calls.
$account = get_account($db, USER_ID);
$filters = [
    array(
        'name'  => 'status',
        'value' => 'running'
    ),
];
$running_scans = get_scans($db, $filters);
$property_filters = [
    array(
        'name'  => 'status',
        'value' => 'active'
    ),
];
$active_property_ids = get_property_ids($db, $property_filters);
$active_properties = get_properties($db, $property_filters);
$uploaded_integrations = uploaded_integrations('../integrations');

// Make sure there are properties to scan.
if($active_property_ids == NULL)
    throw new Exception('You have no active properties to scan');

// Create scan if no other scans are running.
// TODO: Allow multiple scans.
if( count($running_scans) == 0 ){
    add_scan($db, 'running', $active_property_ids);
}else{
    throw new Exception('Only one scan can run at a time');
}

// Load active integrations.
foreach($uploaded_integrations as $uploaded_integration){
    if(is_active_integration($uploaded_integration['uri']))
        require_once '../integrations/'.$uploaded_integration['uri'].'/functions.php';
}

// Scan each active property.
foreach ($active_properties as $property){

    // Run active integration scans.
    foreach($uploaded_integrations as $uploaded_integration){
        if(is_active_integration($uploaded_integration['uri'])){

            // Fire the '_scans' function. 
            $integration_scan_function_name = $uploaded_integration['uri'].'_scans';
            if(function_exists($integration_scan_function_name))
                $integration_scan_function_name($property, $account);

        }
    }

    // Update scanned timestamp.
    update_property_scanned_time($db, $property->id);
    
}

// Subtract account usage.
$properties_count = count($active_property_ids);
add_account_usage($db, USER_ID, $properties_count);

// Add scan record.
update_scan_status($db, 'running', 'complete');

// Redirect
header("Location: ../index.php?view=scans&status=success");