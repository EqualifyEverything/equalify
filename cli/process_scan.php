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

// scan_process helps us redirect to different processes.
$scan_process = DataAccess::get_meta_value('scan_process');

// If this is a new scan, we'll need to set a scan_process.
if(empty($scan_process)){
        
    // process_site is the first process by default.
    DataAccess::update_meta_value('scan_process', 
        'process_site');
    $scan_process = 'process_site';

}

// Let's processs a site!
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
    shell_exec($GLOBALS['PHP_PATH'].' cli/process_site.php > /dev/null 2>/dev/null &');
    exit;

}

// The second process is running integrations.
if($scan_process == 'run_integrations'){

    // Again, we'll redirect to the process so slower 
    // servers avoid getting stuck waiting for  giant
    // process to complete.
    // header('Location: process_integrations.php);
    // exit;

    // TEMPORARY FOR TESTING.
    $scan_process = 'cleanup';

}

// Finally, let's cleanup!
if($scan_process == 'cleanup'){

    // At the end of our processes, we should clear all
    // the meta for the next scan and set the timestamp.
    DataAccess::update_meta_value('scan_process', '');
    DataAccess::update_meta_value('scanable_pages', '');
    DataAccess::update_meta_value(
        'last_scan_time',  date('Y-m-d H:i:s')
    );

}