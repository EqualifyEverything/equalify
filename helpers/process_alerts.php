<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This doc deals with the alerts in a process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Alerts
 * @param array integration_output
 */
function process_alerts( array $integration_output) {

    // From the previous process, we should have
    // the following data that we'll use.
    $processed_urls = $integration_output[
        'processed_urls'];
    $queued_alerts = DataAccess::get_db_rows(
        'queued_alerts', [], 1, 9999999
    );

    // Let's log our process for the CLI.
    update_scan_log("\n\n\n> Processing alerts...");

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Now lets get our existing alerts, filtered to the
    // pages we're interested in.
    $urls_to_filter = array();
    foreach ( $processed_urls as $url){
        array_push($urls_to_filter, array(
            'name'     => 'url',
            'value'    => $url,
            'condition'=> 'OR'
        ));
    };
    $existing_alert_filters = array(
        array(
            'name'  => 'archived',
            'value' => 0
        ),
        array(
            'name'  => 'url',
            'value' => $urls_to_filter
        )
    );
    $existing_alerts = DataAccess::get_db_rows(
        'alerts', $existing_alert_filters, 1, 100000, 'OR'
    )['content'];
    if(empty($existing_alerts))
        $existing_alerts = array();

    // Let's find equalified alerts.
        // Equalified alerts are existing alerts for the site that haven't been queued.
            // Let's mark the status of these alerts "Equalified".

    // Let's add new alerts.
        // New alerts are queued alerts that don't exist in the alerts db.
            // We are comparing the url, message, site_id, type, and source.
                // We add new alerts to the alerts table.

    // Let's log our process for the CLI.
    // $alerts_updated = 
    //     count($equalified_alerts)+count($new_alerts);
    // $alerts_processed = 
    //     count($queued_alerts)+count($existing_alerts);
    // update_scan_log("\n>>> Updated $alerts_updated of $alerts_processed processed alerts.\n");

    // Finally, we'll return a list of sites we processed.
    return $integration_output['processed_sites'];

}