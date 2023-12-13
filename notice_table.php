
<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
 **********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if (!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__ . '/config.php');
require_once(__ROOT__ . '/models/db.php');

// We'll use our new temp method to get back data and print to shell
// We will eventually want to use filters with the existing count_db_rows
// We will possibly need to add a param for which view/section we are focused on
// Let's see how long this takes- start!
$start_time = microtime(true);

$notice_table_data = DataAccess::get_focused_view('notices', [], 'notice_table');
print_r($notice_table_data);

// Finish line
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

// Print result time

echo "TTE: Time to Execute: " .number_format($execution_time, 4) . "seconds\n"; 