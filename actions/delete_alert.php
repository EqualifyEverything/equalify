<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL parameters.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');

// Do the deletion.
$filters = [array(
    'name'  => 'id',
    'value' => $id
)];
delete_alerts($db, $filters);

// When the work is done, we can triumphantly return home.
header("Location: ../index.php?view=alerts&status=success");