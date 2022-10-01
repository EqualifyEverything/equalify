<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document deletes reports.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Setup variables.
$alert_report = $_GET['name'];

// Delete DB 
$filtered_to_report = array(
    array(
        'name' => 'meta_name',
        'value' => $_GET['name'],
    )
);
DataAccess::delete_db_entries(
    'meta', $filtered_to_report
);

// Reload alerts page.
header('Location: ../index.php?view=alerts');