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
$current_status = $_GET['current_status'];
if(empty($current_status))
    throw new Exception('Status is not specfied for property "'.$id.'"');

// Toggle Property Status
if($current_status == 'active' || $current_status == 'unscanned'){
    archive_property($db, $id);
    archive_property_children($db, $id);
}
if($current_status == 'archived'){
    activate_property($db, $id);
    activate_property_children($db, $id);
}

// Redirect
header('Location: ../index.php?view=property_details&id='.$id.'&status=success');
die();