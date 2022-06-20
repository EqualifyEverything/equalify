<?php
// ***************!!EQUALIFY IS FOR EVERYONE!!***************
// This is the scanner, the most important part of Equalify.
// 
// The goal is to scan an unlimited number of pages on the 
// most basic Digital Ocean droplet that is tuned with a 
// standard LEMP install. You'll notice many Equalify files
// include the follwing comment to restrict the length of 
// lines and keep us to our mission of building an app that
// people, even on the most basic droplet, can use. Enjoy!
//
// ***************!!EQUALIFY IS FOR EVERYONE!!***************

// This document is going to use the DB.
require_once '../config.php';
require_once '../models/db.php';

// scan_process helps us redirect to different processes.
$scan_process = DataAccess::get_meta_value('scan_process');

// If scan has no process set, set it to the first process.
if(empty($scan_process)){
    DataAccess::add_meta_value('scan_process', 
        'process_site');
    $scan_process = 'process_site';
}

// The first process is to process a site.
if($scan_process == 'process_site'){
            
    // We'll redirect to a seperate page so slower servers 
    // don't get stuck waiting for big processes to 
    // complete.
    header('Location: process_site.php');
    exit;

}

// The second process is running integrations.
if($scan_processs == 'run_integrations'){

    // Again, we'll redirect to the process so slower 
    // servers avoid getting stuck waiting for  giant
    // process to complete.
    header('Location: process_integrations.php');
    exit;

}

// At the end of our processes, we can delete the 
// scan_process and update the status to 'complete'.
DataAccess::update_meta_value('scan_process', '');
DataAccess::update_meta_value('scan_status', 'complete');