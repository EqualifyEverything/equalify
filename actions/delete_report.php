<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document deletes reports.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Info on DB must be declared to use db.php models.
require_once '../init.php';
require_once '../models/db.php';

// Delete DB 
$filtered_to_report = array(
    array(
        'name' => 'meta_name',
        'value' => $_GET['report'],
    )
);
DataAccess::delete_db_entries(
    'meta', $filtered_to_report
);

// Reload reports page.
header('Location: ../index.php?view=reports');