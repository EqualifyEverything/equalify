<?php

// Add DB info and required functions.
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/view_components.php';
require_once '../models/integrations.php';


// Setup queries to minize db calls.
$page_filters = [
    array(
        'name'  => 'status',
        'value' => 'active'
    ),
];
$active_page_ids = DataAccess::get_page_ids($page_filters);
$active_pages = DataAccess::get_pages($page_filters);
$uploaded_integrations = uploaded_integrations('../integrations');

// Make sure there are pages to scan.
if($active_page_ids == NULL)
    throw new Exception('You have no active pages to scan');

// Adding a scan to display a running task before scans complete.
if( $_GET['action'] == 'add_scan' ){
    DataAccess::add_scan('running', $active_page_ids);
    $scans = DataAccess::get_scans();
    the_scan_rows($scans);
}

// All the scan heavy lifting goes here.
if( $_GET['action'] == 'do_scan' ){

    // This count helps us know how many pages were scanned.
    $pages_count = 0;

    // Get active integrations.
    $active_integrations = unserialize(DataAccess::get_meta_value('active_integrations'));

    // We're requring active integrations here and not in
    // the next loop because we don't want to require the
    // files over and over again.
    foreach($uploaded_integrations as $uploaded_integration){
        if ( array_search($uploaded_integration['uri'], $active_integrations) !== false)
            require_once '../integrations/'.$uploaded_integration['uri'].'/functions.php';
    }

    // Scan each active page.
    foreach ($active_pages as $page){
        $pages_count++;

        // Run active integration scans.
        foreach($uploaded_integrations as $uploaded_integration){
            if ( array_search($uploaded_integration['uri'], $active_integrations) !== false){

                // Fire the '_scans' function. 
                $integration_scan_function_name = $uploaded_integration['uri'].'_scans';
                if(function_exists($integration_scan_function_name)){

                    // We need to kill the scan if an integration has an error.
                    try {
                        $integration_scan_function_name($page);

                    } catch (Exception $x) {

                        // We will kill the scan and alert folks of any errors, but
                        // we will also record the successful scans that occured.
                        $existing_usage = DataAccess::get_meta_value('usage');
                        if($existing_usage == false){

                            // This might be the first time we run a scan, so 
                            // we need to create the meta field if none exists.
                            DataAccess::add_meta('usage', $pages_count);

                        }else{
                            DataAccess::update_meta_value('usage', $pages_count+$existing_usage);
                        }
                        DataAccess::add_integration_alert($x->getMessage());
                        DataAccess::update_scan_status('running', 'incomplete');
                        $scans = DataAccess::get_scans();
                        the_scan_rows($scans);
                        die;

                    }

                }

            }
        }

        // Successful scans get a timestamp.
        DataAccess::update_page_scanned_time($page->id);
        
    }    

    // We're updating the count at the end of the scan
    // because we only want to count succsefully scanned
    // pages.
    $pages_count = count($active_page_ids);

    // We keep track of the amount of pages scanned.
    $existing_usage = DataAccess::get_meta_value('usage');
    if($existing_usage == NULL){

        // This might be the first time we run a scan, so 
        // we need to create the meta field if none exists.
        DataAccess::add_meta('usage', $pages_count);

    }else{
        DataAccess::update_meta_value('usage', $pages_count+$existing_usage);
    }

    // Sacn is complete!
    DataAccess::update_scan_status('running', 'complete');

    // Scan info is passed to JSON on the view, so that we can do 
    // async scans.
    $scans = DataAccess::get_scans();
    the_scan_rows($scans);
    
}

// This changes the little red number asyncronistically with JS
// embedded in the view file.
if( $_GET['action'] == 'get_alerts' ){
    echo count(DataAccess::get_alerts());
}