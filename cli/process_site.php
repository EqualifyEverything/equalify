<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We use this document to process a site, so it's ready 
 * to be delivered to integrations. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// This document is going to use the DB and adders.
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/adders.php';

// The main purpose of this process is to declare the 
// 'scanable_pages' meta, which may have been created.
$scanable_pages = unserialize(
    DataAccess::get_meta_value('scanable_pages')
);
if(empty($scanable_pages)){
    $scanable_pages = array();
}

// Let's get sites_processing, which was setup in 
// processs_scan.php.
$sites_processing = unserialize( 
    DataAccess::get_meta_value( 'sites_processing')
);

// Each site is processed individually. We'll always run it
// on the first site in the loop, until there are no sites.
if(!empty($sites_processing)){
    $site = $sites_processing[0];
}else{
    $site = '';
}
if(!empty($site)){

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

    // When we're done we can push the scannable pages
    // to the DB..
    DataAccess::update_meta_value( 'scanable_pages', 
        serialize($scanable_pages)
    );

    // .. and remove the site from sites_processing.
    unset($sites_processing[0]);
    $sites_processing_reset = 
        array_values($sites_processing);
    DataAccess::update_meta_value( 'sites_processing', 
        serialize($sites_processing_reset)
    );

    // Now we can run the process again - we want to limit
    // the length of the process and a curl of every site 
    // page can be a cumbersome process that drags down on 
    // slower servers.
    shell_exec(
        $GLOBALS['PHP_PATH'].
        ' cli/process_site.php'
    );
    die;

}