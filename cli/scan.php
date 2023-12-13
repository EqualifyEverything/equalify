<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This is the scanner, Equalify's core. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
    
// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');
require_once(__ROOT__.'/helpers/process_sites.php');
require_once(__ROOT__.'/helpers/scan.php');
require_once(__ROOT__.'/helpers/process_integrations.php');
require_once(__ROOT__.'/helpers/process_notices.php');
require_once(
    __ROOT__.'/helpers/process_integrations.php'
);

// At the start of our processes, set the scan time.
DataAccess::update_meta_value(
    'last_scan_time',  date('Y-m-d H:i:s')
);

// We'll log time because our goal is to finish scans
// as quickly as possible.
$starting_time = microtime(true);
update_scan_log(
    "\nStarted scanning on ".date('d/m/Y')." at ".date('h:i:s')."."
);

// Our first process.
try {
    $sites_output = process_sites();
}
catch(Exception $error) {
    kill_scan($error->getMessage());
}

// Our second process.
try {
    $integration_output = process_integrations(
        $sites_output
    );
}
catch(Exception $error) {
    kill_scan($error->getMessage());
}

// Our third process.
try {
    $notices_output = process_notices($integration_output);
}
catch(Exception $error) {
    kill_scan($error->getMessage());
}

// We initiate our processes.
if(!empty($notices_output)){

    // We're updating the scanned time of each site. 
    $fields = array(
        array(
            'name' => 'scanned',
            'value' => date('Y-m-d H:i:s')
        )
    );

    // We find sites that match the URL.
    $filters = array();
    foreach ($notices_output as $site){
        array_push( $filters,
            array(
                'name' => 'url',
                'value' => $site->url,
                'condition' => 'OR'
            )
        );
    }

    // Let's update the site!
    DataAccess::update_db_rows(
        'properties', $fields, $filters
    );

}

// Log our progress.
$ending_time = microtime(true);
$exec_time = $ending_time - $starting_time;
update_scan_log("\n\nScan complete.");
update_scan_log(
    "\n\n\nEqualify scan took just $exec_time seconds."
);

// After a successful scan, we set a timestamp.
DataAccess::update_meta_value(
    'last_scan_time',  date('Y-m-d H:i:s')
);

// Finally, let's clear the scan status, which should
// have been set when initiating the scan.
DataAccess::update_meta_value('scan_status', '');