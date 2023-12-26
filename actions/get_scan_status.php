<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script gets scan_status from the DB.
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
require_once(__ROOT__.'/init.php');
require_once(__ROOT__.'/models/db.php');

// Return the scan log.
echo DataAccess::get_meta_value('scan_status');