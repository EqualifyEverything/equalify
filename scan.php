<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script runs the scanner. It should be very basic
 * because it's hit on every page load, so we don't have
 * to add extra CRON work that would require additional
 * installation procedures that most users would hate.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// We only run scans every 24 hours. Let's set that
// interval here, so we can quickly update it later.
$scan_interval = '1 day';

// scan_time is set at the end of the scan process.
$scan_time = DataAccess::get_meta_value(
    'last_scan_time'
);

// When Equalify is istalled, scan_time is empty, so we 
// know we can just run the scan then.
if(empty($scan_time)){
    run_scan();

// If a scan time is set, we have to run further checks.
}else{

    // When scan time is not empty we should set the time 
    // of the next scan.te
    $scan_time = new DateTime($scan_time); 
    $next_scan_time = $scan_time->modify('+1 day');

    // Is the scan_time after the current time?
    if( $next_scan_time->format('Y-m-d H:i:s') < date('Y-m-d H:i:s') ){

        // We don't want any scan process to be running,
        //  so we don't overwrite an existing process.
        $scan_process = DataAccess::get_meta_value(
            'scan_process'
        );

        // All checks complete, let's trigger the scan.
        if(empty($scan_process))
            run_scan();
            
    }

}

/**
 * Run Scan
 */
function run_scan(){

    // The scan runs in the background.
    shell_exec($GLOBALS['PHP_PATH'].' cli/process_scan.php > /dev/null 2>/dev/null &');
    
}