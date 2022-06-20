<?php
// ***************!!EQUALIFY IS FOR EVERYONE!!***************

// This document is going to use the DB and adders.
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/adders.php';

// The main purpose of this process is to declare the 
// 'scanable_pages' meta, which may have been created.
$scanable_pages = unserialize(
    DataAccess::get_meta_value('scanable_pages')
);
if(empty($scanable_pages)){
    DataAccess::add_meta('scanable_pages');
    $scanable_pages = [];
}

// We are only going to process active sites that are
// unprocessed.
$filtered_to_active_sites = array(
    array(
        'name' => 'status',
        'value' => 'active'
    ), array(
        'name'  => 'processed',
        'value' => false
    )
);
$sites_processing = DataAccess::get_sites(
    $filtered_to_active_sites
);
if(!empty($sites_processing)){

    // We want to process the first site that isn't 
    // processed yet.
    foreach ($sites_processing as $site){
        if($site->processed == false){

            // Processing a site means adding its pages as 
            // scannable_pages meta, which we do with our 
            // adders.
            if($site->type == 'single_page'){
                $site_pages = single_page_adder($site->url);
            }
            if($site->type == 'xml'){
                $site_pages = xml_site_adder($site->url);
            }
            if($site->type == 'wordpress'){
                $site_pages = wordpress_site_adder($site->url);
            }

            // Let's add these pages to scanable_pages.
            array_push($scanable_pages, $site_pages);
            DataAccess::update_meta_value( 'scanable_pages', 
                serialize($scanable_pages)
            );
            
            // Let's also change the processed value.
            DataAccess::update_page_meta($site->url, 'processed',
                true);
            
            // Now we can reload the page to run the process again 
            // - this may seem unnessary, but we want to limit the 
            // length of the process and a curl of every site page 
            // can be a cumbersome process that drags down on 
            // slower servers.
            header('Refresh:0');
            exit;

        }
    }

}

// Once we've iterated through the process, we can clear out
// sites_processing..
DataAccess::update_meta_value( 'sites_processing', '');

// ..and update the meta value to the next process..
DataAccess::update_meta_value(
    'scan_process', 'run_integrations');

// ..before continuing to process the scan.
header('Location: process_scan.php');
exit;