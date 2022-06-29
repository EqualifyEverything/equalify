<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We use this document to process a site, so it's ready 
 * to be delivered to integrations. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// First, let's log our process
echo "\n\n\n> Processing sites...";

// This document uses the DB and adders.
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/adders.php';

// The main purpose of this process is to declare the 
// 'scanable_pages' meta, which may have been created.
$scanable_pages = unserialize(
    DataAccess::get_meta_value('scanable_pages')
);
if(empty($scanable_pages)){

    // This must be an array so we can properly us it
    // again from the db.
    $scanable_pages = array();

}

// We're only going to run this process on active sites.
$filtered_to_active_sites = array(
    array(
        'name' => 'status',
        'value' => 'active'
    )
);
$active_sites = DataAccess::get_sites(
    $filtered_to_active_sites
);

// Log our progress..
$active_sites_count = count( $active_sites);
echo "\n> $active_sites_count active site";
if($active_sites_count > 1 ){
    echo 's';
}
echo ':';

// We only run this process if there are sites ready to
// process.
if(!empty($active_sites)){

    // Each site is processed individually.
    foreach($active_sites as $site){

        // Log our progress.
        echo "\n>>> Processing \"$site->url\".";

        // Processing a site means adding its site_pages as 
        // scanable_pages meta, which we do with adders.
        if($site->type == 'single_page'){
            $site_pages = single_page_adder($site->url);
        }
        if($site->type == 'xml'){
            $site_pages = xml_site_adder($site->url);
        }
        if($site->type == 'wordpress'){
            $site_pages = wordpress_site_adder($site->url);
        }

        // Let's add these pages to new_scanable_pages.
        foreach ($site_pages as $page){
            array_push($scanable_pages, $page);        
        }

    }

    // When we're done we can push the scannable_pages to
    // the DB.
    DataAccess::update_meta_value( 'scanable_pages', 
        serialize($scanable_pages)
    );

    // Log our progress.
    $pages_count = count($scanable_pages);
    echo "\n> Found $pages_count scanable pages.";
    
}