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

// We keep track of the scan process in the DB, so we can
// see if the scan is running in other areas of our app.
DataAccess::update_meta_value( 'scan_process', 
    'running'
);

// We'll keep a log and time what's happening.
echo "\n\n\nLet's Equalify some sites!";
$time_pre = microtime(true);

// We'll create `sites_processing` with active sites,
// which will be used to generate `scanable_pages`.
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

// Log our progress..
$active_sites_count = count( $active_sites);
echo "\n\n\n> Processing $active_sites_count sites...";
    
// Noq we'll start our first process.
shell_exec(
    $GLOBALS['PHP_PATH'].
    ' cli/process_site.php'
);

// Log our progress.
$site_pages_count = count(
    unserialize(
        DataAccess::get_meta_value('scanable_pages')
    )
);
echo "\n>>> There are $site_pages_count scanable pages.";

// Now, let's setup `integrations_processing,` which
// records all the active integrations for us to use
// in the process.
$active_integrations = 
    DataAccess::get_meta_value('active_integrations');
DataAccess::update_meta_value('integrations_processing', 
    $active_integrations
);

// Log our progress..
$active_integrations_count = count(  unserialize($active_integrations));
echo "\n\n\n> Processing $active_integrations_count integrations...";

// Like all our processes, integrations are run in the
// background.
shell_exec(
    $GLOBALS['PHP_PATH'].
    ' cli/process_integration.php'
);

// At the end of our processes, we should clear all
// the meta for the next scan and set the timestamp.
DataAccess::update_meta_value('scan_process', '');
DataAccess::update_meta_value('scanable_pages', '');
DataAccess::update_meta_value(
    'last_scan_time',  date('Y-m-d H:i:s')
);

// Log our progress..
$time_post = microtime(true);
$exec_time = $time_post - $time_pre;
$alerts_count = number_format(DataAccess::count_alerts());
echo "\n\n\nEqualify logged $alerts_count alerts in just $exec_time seconds.\n\n\n";