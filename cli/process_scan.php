<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// This document is going to use the DB.
require_once 'config.php';
require_once 'models/db.php';

// We keep track of the scan process in the DB, so we can
// see if the scan is running in other areas of our app.
DataAccess::update_meta_value( 'scan_status', 
    'running'
);

// We'll keep a log, time, and alert count because our goal
// is to find as many alerts as possible in as short a time
// as possible..
echo "\n\n\nLet's Equalify some sites!";
$starting_time = microtime(true);
$starting_alerts_count = DataAccess::count_alerts();
    
// Now we'll start our first process.
require_once('cli/process_sites.php');

// Time to run the integrations!
require_once('cli/process_integrations.php');

// At the end of our processes, we should clear all
// the meta for the next scan and set the timestamp.
DataAccess::update_meta_value('scan_status', '');
DataAccess::update_meta_value('scanable_pages', '');
DataAccess::update_meta_value(
    'last_scan_time',  date('Y-m-d H:i:s')
);

// Log our progress..
$ending_time = microtime(true);
$exec_time = $ending_time - $starting_time;
$ending_alerts_count = DataAccess::count_alerts();
$added_alerts = number_format(
    $ending_alerts_count - $starting_alerts_count
);
echo "\n\n\nEqualify logged $added_alerts new alerts in just 
    $exec_time seconds.\n\n\nHow can Equalify do better?\n\n\n";