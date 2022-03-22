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

// Set ID (Filtered)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Fallback
if(empty($id))
    throw new Exception('ID is invalid.');

// Delete Site, Pages, and Events
delete_property($db, $id);
delete_property_pages($db, $id);
delete_property_events($db, $id);

// Redirect
header("Location: ../index.php?view=properties&status=success");
die();