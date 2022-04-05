<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL variables and fallbacks.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for property "'.$id.'"');

// Toggle property status.
$group = get_property($db, $id)->group;
if($old_status == 'active'){
    update_property_group_status($db, $group, 'archived');

    // Alerts are deleted when a property is archived.
    $filters = [
        array(
            'name'  => 'property_group',
            'value' => $group
        )
    ];
    delete_alerts($db, $filters);
    
}
if($old_status == 'archived'){
    update_property_group_status($db, $group, 'active');
}

// Redirect
header('Location: ../index.php?view=property_details&id='.$id.'&status=success');