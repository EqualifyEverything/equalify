<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');
require_once(__ROOT__.'/helpers/process_sites.php');
require_once(__ROOT__.'/helpers/update_scan_log.php');
require_once(__ROOT__.'/helpers/process_integrations.php');
require_once(__ROOT__.'/helpers/process_alerts.php');
require_once(
    __ROOT__.'/helpers/process_integrations.php'
);

/**
 * Scan
 */
function scan(){

    // Let's clear out any old log files.
    DataAccess::update_meta_value('scan_log', '');

    // We keep track of the scan process in the DB to see
    // if the scan is running in other areas of our app.
    DataAccess::update_meta_value( 'scan_status', 
        'running'
    );

    // We'll log time and alert count because our goal is
    // to find as many alerts as possible in as short a
    // time as possible..
    update_scan_log("\nBegin scan:");
    $starting_time = microtime(true);
    $starting_alerts_count = DataAccess::count_db_rows(
        'alerts'
    );
        
    // Our first process.
    $sites_output = process_sites();

    // Our second process.
    $integration_output = process_integrations(
        $sites_output
    );

    // Our third process.
    $alerts_output = process_alerts($integration_output);

    // We initate our processes.
    if(!empty($alerts_output)){

        // We're updating the scanned time of each site. 
        $fields = array(
            array(
                'name' => 'scanned',
                'value' => date('Y-m-d H:i:s')
            )
        );

        // We find sites that match the URL.
        $filters = array();
        foreach ($alerts_output as $site){
            array_push( $filters,
                array(
                    'name' => 'url',
                    'value' => $site->url
                )
            );
        }

        // Let's update the site!
        DataAccess::update_db_rows(
            'sites', $fields, $filters, 'OR'
        );

    }

    // At the end of our processes, set the scan time.
    DataAccess::update_meta_value(
        'last_scan_time',  date('Y-m-d H:i:s')
    );

    // Log our progress..
    $ending_time = microtime(true);
    $exec_time = $ending_time - $starting_time;
    $ending_alerts_count = DataAccess::count_db_rows(
        'alerts'
    );
    $added_alerts = number_format(
        $ending_alerts_count - $starting_alerts_count
    );

    update_scan_log("\n\nScan complete.");
    update_scan_log("\n\nEqualify added $added_alerts alerts ");
    update_scan_log("in just $exec_time seconds.");

    // Finally, let's clear the scan status and log.
    DataAccess::update_meta_value('scan_status', '');
    
}

// Only one scan runs at a time.
if(
    DataAccess::get_meta_value('scan_status')
    != 'running'
):

    // Promised handle errors.
    try {

        // Initiate scan.
        scan();

    } catch (Exception $e) {

        // When an error occurs, we update the scan status.
        DataAccess::update_meta_value(
            'scan_status', 'Failed: '.$e->getMessage()
        );

        // Let's log the erorr for CLI.
        update_scan_log("\nCaught exception: ",  $e->getMessage(), "\n");


    } finally {

        // After a successful scan, we cleanup the db and
        // set a timestamp.
        DataAccess::update_meta_value(
            'last_scan_time',  date('Y-m-d H:i:s')
        );

    }

// Fallback if a scan is running.
else:

    // Fallback.
    update_scan_log("\n  A scan is running. Multiple scans can't run.\n\n");

endif;