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

// Get URL variabls and fallbacks
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for property "'.$id.'"');

// Toggle Property Status
if($old_status == 'active'){
    update_property_status($db, $id, 'archived');
    update_property_children_status($db, $id, 'archived');
}
if($old_status == 'archived'){
    update_property_status($db, $id, 'active');
    update_property_children_status($db, $id, 'active');
}

// Redirect
header('Location: ../index.php?view=property_details&id='.$id.'&status=success');
die();