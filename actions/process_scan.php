<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// This document is going to use the DB.
require_once '../config.php';
require_once '../models/db.php';

// We start by retrieving the scan that we want to process,
// which may have already been set
$current_scan = DataAccess::get_current_scan_id();
if(empty($current_scan)){
    
    // If we didn't retrieve a scan ID, it must mean that 
    // we should get the next scan that is queued.
    $next_scan = DataAccess::get_next_scan();
    $current_date = date('Y-m-d H:i:s');
    if(!empty($next_scan)){
        if($current_date > $next_scan->time){
    
            // Let's set the $current_scan.
            $current_scan = $next_scan->id;
            DataAccess::update_meta_value('current_scan', 
            'process_site');
        
        }
    }

}

// scan_process helps us redirect to different processes.
$scan_process = DataAccess::get_meta_value('scan_process');

// We may have already setup the process, since this file
// is hit multiple times.
if(empty($scan_process)){

    // When we're about to take on a new process, we need
    // to make sure no scan is running since this file
    // can be triggered via HTTP.
    $filtered_to_running = array(
        array(
            'name' => 'status',
            'value' => 'running'
        )
    );
    $scans = DataAccess::get_scans($filtered_to_running);
    if(empty($scans)){
        
        // process_site is the first process by default.
        DataAccess::update_meta_value('scan_process', 
            'process_site');
        $scan_process = 'process_site';

        // We'll also need to update the scan status.
        DataAccess::update_scan_status(
            $current_scan, 'running'
        );

    }else{

        // Somethings wrong if we triggered a process scan
        // white the scan is running.
        $scan_process = 'fail';

    }

}

// The first process is to process a site.
if($scan_process == 'process_site'){

    // We only use active pages for sites_processing.
    $sites_processing = [];
    $filtered_to_active_sites = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_sites = DataAccess::get_sites(
        $filtered_to_active_sites
    );
    DataAccess::update_meta_value( 'sites_processing', 
        serialize($active_sites)
    );
    
    // We'll redirect to a seperate page so slower servers 
    // don't get stuck waiting for big processes to 
    // complete.
    header('Location: process_site.php?current_scan='.$current_scan);
    exit;

}

// The second process is running integrations.
if($scan_process == 'run_integrations'){

    // Again, we'll redirect to the process so slower 
    // servers avoid getting stuck waiting for  giant
    // process to complete.
    // header('Location: process_integrations.php?current_scan='.$current_scan);
    // exit;

    // TEMPORARY FOR TESTING.
    $scan_process = 'cleanup';

}

// Failures may happen..
if($scan_process == 'fail'){
    DataAccess::update_scan_status($current_scan, 'failed');
}

// Finally, let's cleanup!
if($scan_process == 'cleanup'){

    // At the end of our processes, we can delete the 
    // scan_process and update the status to 'complete'.
    DataAccess::update_meta_value('scan_process', '');
    DataAccess::update_meta_value('current_scan', '');
    DataAccess::update_scan_status($current_scan, 'complete');

}