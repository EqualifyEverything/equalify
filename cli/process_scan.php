<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file is meant for CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');
require_once(__ROOT__.'/helpers/process_sites.php');
require_once(__ROOT__.'/helpers/process_equalify.php');
require_once(__ROOT__.'/helpers/process_integrations.php');
require_once(
    __ROOT__.'/helpers/process_integrations.php'
);

/**
 * Scan
 */
function scan(){

    // We keep track of the scan process in the DB to see
    // if the scan is running in other areas of our app.
    DataAccess::update_meta_value( 'scan_status', 
        'running'
    );

    // We'll log time and alert count because our goal is
    // to find as many alerts as possible in as short a
    // time as possible..
    echo "\n\n\nLet's Equalify some sites!";
    $starting_time = microtime(true);
    $starting_alerts_count = DataAccess::count_db_rows(
        'alerts'
    );
        
    // Now we'll start our first process.
    process_sites();

    // Time to run the integrations!
    process_integrations();

    // Finally, we can equalify alerts.
    process_alerts();

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
    $ending_alerts_count = DataAccess::count_db_rows('alerts');
    $added_alerts = number_format(
        $ending_alerts_count - $starting_alerts_count
    );
    echo "\n\n\nEqualify logged $added_alerts new alerts in just 
        $exec_time seconds.\n\n\nHow can Equalify do better?\n\n\n";
    
}

/**
 * Cleanup
 */
function cleanup(){

    // We need to clear all the meta when a scan stops.
    DataAccess::update_meta_value('scan_status', '');
    DataAccess::update_meta_value('scanable_pages', '');

}

// Promised handle errors.
try {

    // Let's run the process.
    scan();

} catch (Exception $e) {

    // When an error occurs, we have to clear scan
    // variables that affect future scans.
    cleanup(); 

    // Let's log the erorr for CLI.
    echo "\nCaught exception: ",  $e->getMessage(), "\n";


} finally {

    // After a successful scan, we cleanup the db and
    // set a timestamp.
    cleanup(); 
    DataAccess::update_meta_value(
        'last_scan_time',  date('Y-m-d H:i:s')
    );

}
