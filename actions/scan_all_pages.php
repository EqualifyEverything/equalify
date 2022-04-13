<?php

// Add DB info and required functions.
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/view_components.php';
require_once '../models/integrations.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Setup queries to minize db calls.
$meta = get_meta($db);
$page_filters = [
    array(
        'name'  => 'status',
        'value' => 'active'
    ),
];
$active_page_ids = get_page_ids($db, $page_filters);
$active_pages = get_pages($db, $page_filters);
$uploaded_integrations = uploaded_integrations('../integrations');

// Make sure there are pages to scan.
if($active_page_ids == NULL)
    throw new Exception('You have no active pages to scan');

// Adding a scan to display a running task before scans complete.
if( $_GET['action'] == 'add_scan' ){
    add_scan($db, 'running', $active_page_ids);
    $scans = get_scans($db);
    the_scan_rows($scans);
}

// All the scan heavy lifting goes here.
if( $_GET['action'] == 'do_scan' ){

    // This count helps us know how many pages were scanned.
    $pages_count = 0;


    // We're requring active integrations here and not below
    // because we don't want to require the files over and 
    // over again.
    foreach($uploaded_integrations as $uploaded_integration){
        if(is_active_integration($uploaded_integration['uri']))
            require_once '../integrations/'.$uploaded_integration['uri'].'/functions.php';
    }

    // Scan each active page..
    foreach ($active_pages as $page){
        $pages_count++;

        // Run active integration scans.
        foreach($uploaded_integrations as $uploaded_integration){
            if(is_active_integration($uploaded_integration['uri'])){

                // Fire the '_scans' function. 
                $integration_scan_function_name = $uploaded_integration['uri'].'_scans';
                if(function_exists($integration_scan_function_name)){

                    // We need to kill the scan if an integration has an error.
                    try {
                        $integration_scan_function_name($page, $meta);

                        // Only successful scans get their timestamp updated.
                        update_page_scanned_time($db, $page->id);

                    } catch (Exception $x) {

                        // We will kill the scan and alert folks of any errors, but
                        // we will also record the successful scans that occured.
                        update_usage_meta($db, $pages_count);
                        add_integration_alert($db, $x->getMessage());
                        update_scan_status($db, 'running', 'incomplete');
                        die;

                    }

                }

            }
        }
        
    }    

    // We're updating the count at the end of the scan
    // because we only want to count succsefully scanned
    // pages.
    $pages_count = count($active_page_ids);


    update_usage_meta($db, $pages_count);
    update_scan_status($db, 'running', 'complete');

    // Scan info is passed to JSON on the view, so that we can do 
    // async scans.
    $scans = get_scans($db);
    the_scan_rows($scans);
    
}

// This changes the little red number asyncronistically with JS
// embedded in the view file.
if( $_GET['action'] == 'get_alerts' ){
    echo count(get_alerts($db));
}