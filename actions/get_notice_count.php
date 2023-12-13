<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script gets scan_log from the DB.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// Info on DB must be declared to use db.php models.
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');

// Return the scan log.
$filtered_to_active_status = array(
    array(
        'name' => 'status',
        'value' => 'active'
    ),
    array(
        'name' => 'archived',
        'value' => 0
    )
);
echo number_format(DataAccess::count_db_rows(
    'notices', $filtered_to_active_status
));