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

// Delete Site and Pages Record
delete_site($db, $id);
delete_site_pages($db, $id);
delete_site_events($db, $id);

// Redirect
header("Location: ../index.php?view=sites&status=success");
die();