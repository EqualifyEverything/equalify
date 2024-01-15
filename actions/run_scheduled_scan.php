<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script runs the scanner. It should be very basic
 * because it's hit on every page load, so we don't have
 * to add extra CRON work that would require additional
 * installation procedures that most users would hate.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__.'/helpers/scan.php');

// The scan_schedule meta value sets the interval.
$scan_schedule = DataAccess::get_meta_value(
    'scan_schedule'
);

// We only run the scan automatically if the user doesn't
// want to run it manually.
if($scan_schedule != 'manually'):

    // Let's translate the schedule to something PHP can
    // read.
    if($scan_schedule == 'hourly'){
        $scan_interval = '1 hour';
    }elseif($scan_schedule == 'daily'){
        $scan_interval = '1 day';
    }elseif($scan_schedule == 'weekly'){
        $scan_interval = '1 week';
    }elseif($scan_schedule == 'monthly'){
        $scan_interval = '1 month';
    }

    // scan_time tells us when a scan was run.
    $scan_time = DataAccess::get_meta_value(
        'last_scan_time'
    );

    // When Equalify is installed, scan_time is empty, so we 
    // know we can just run the scan then.
    if(empty($scan_time)){

        // Let's run the scan.
        run_scan();

    // If a scan time is set, we have to run further checks.
    }else{

        // When scan time is not empty, we should set the time 
        // of the next scan.
        $scan_time = new DateTime($scan_time); 
        $next_scan_time = $scan_time->modify('+'.$scan_interval);

        // Is the scan_time after the current time?
        if( 
            $next_scan_time->format('Y-m-d H:i:s') 
            < date('Y-m-d H:i:s') 
        ){

            // Run scan.
            run_scan();
            
        }

    }

endif;