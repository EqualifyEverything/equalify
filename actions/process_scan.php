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

// scan_process helps us redirect to different processes.
$scan_process = DataAccess::get_meta_value('scan_process');

// If scan has no process set, set it to the first process.
if(empty($scan_process)){
    DataAccess::update_meta_value('scan_process', 
        'process_site');
    $scan_process = 'process_site';
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
    header('Location: process_site.php');
    exit;

}

// The second process is running integrations.
if($scan_process == 'run_integrations'){

    // Again, we'll redirect to the process so slower 
    // servers avoid getting stuck waiting for  giant
    // process to complete.
    // header('Location: process_integrations.php');
    exit;

}

// At the end of our processes, we can delete the 
// scan_process and update the status to 'complete'.
DataAccess::update_meta_value('scan_process', '');
DataAccess::update_meta_value('scan_status', 'complete');