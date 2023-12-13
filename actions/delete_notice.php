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
session_start();
// if ($_SESSION['notice_id']) {
    // Archived will be removed when added to view
    $filtered_to_notice = array(
        array(
            'name' => 'id',
            'value' => $_GET['notice_id'],
        )
    );
    DataAccess::delete_db_entries(
        'notices',
        $filtered_to_notice
    );
// } else {
//     throw new Exception('Cannot delete notice without ID.');
// }

// Reload reports page.
header('Location: ../index.php?view=reports');
