<?php
// ***************!!EQUALIFY IS FOR EVERYONE!!***************

// This document is going to use the DB.
require_once '../config.php';
require_once '../models/db.php';

// The main purpose of this process is to run integrations,
// which will be recorded via 'integrations_processing'.
$integrations_processing = unserialize(
    DataAccess::get_meta_value('integrations_processing')
);
if(empty($integrations_processing)){

    // When integrations_processing isn't setup, we need to
    // add the meta with active integrations.
    $active_integrations = 
        DataAccess::get_meta_value('active_integrations');
    DataAccess::add_meta('integrations_processing', 
        $active_integrations
    );
    $integrations_processing = $active_integrations;

    // We'll also need to setup the scanabale_pages, so that
    // we can see which page was scanned by what integration.
    $scanable_pages = DataAccess::get_meta_value(
        'scanable_pages'
    );

    // Every scanable page gets a metafield for the 
    // integration that scanned it.
    foreach ($scanable_pages as $page){
        $page['integration_scanned'] = '';
    }
    DataAccess::update_meta_value( 'scanable_pages', 
        serialize($scanable_pages)
    );

}

// Now we run each integration.
foreach ($integrations_processing as $integration){

    // We'll run each integration against pages that
    // the integration hasn't scanned.
    $unscanned_page = DataAccess::get_unscanned_page(
        $integration
    );

    // If there's still a page to scan, we can run the
    // integration.
    if(!empty($unscanned_page)){

        // Every integration should use the same pattern
        // to run thier scan functions.
        $integration_scan_function = $integration.'_scans';
        if(function_exists($integration_scan_function)){
            try {

                // Do integration function.
                $integration_scan_function(
                    $unscanned_page->url
                );

            } catch (Exception $x) {

                // We will kill the scan and alert folks 
                // of any errors.
                DataAccess::add_alert(
                    'system', $url, $site, NULL, 'error',
                    $x->getMessage(), NULL
                );
                DataAccess::update_scan_status(
                    $scan_id, 'incomplete'
                );
                die;

            }
        }

        // When the scan is successful, we'll update the
        // page meta so that we don't scan it again.
        DataAccess::add_integration_scanned_to_page(
            $unscanned_page, $integration
        );

        // Now we can reload the page to run the process 
        // again - this may seem unnessary, but we want 
        // to limit the  length of the process and a curl 
        // of every site page can be a cumbersome process 
        // that drags down on slower servers.
        header('Refresh:0');
        exit;

    }

    // Once there's no more unscanned pages, we can 
    // remove the integration from the array.
    DataAccess::delete_integration_processing_value(
        $integration
    );

}

// When the process completes all its iterations, we can 
// return to the processor.
header('Location: process_scan.php');
exit;