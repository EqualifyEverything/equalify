<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This doc deals with the alerts in a process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
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
    $queued_alerts = $integration_output['queued_alerts'];

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

    // We'll need to prepare existing alerts to be compared.
    foreach($existing_alerts as $key => $existing_alert){

        // Let's convert the array's objects to arrays.
        $converted_alert = (array) $existing_alert;
        array_push(
            $existing_alerts, $converted_alert
        );
        unset($existing_alerts[$key]);

    }
    echo "\n** ".count($existing_alerts);

    // We'll need to remove duplicates from alerts.
    function remove_duplicates ($a_array, $b_array){
        foreach($a_array as $key => $a){
            foreach($b_array as $b){

                // The source, url, type, and message
                // is compared.
                $url_equal = $a['url'] === $b['url'];
                $message_equal = $a['message'] === $b['message'];
                $type_equal = $a['type'] === $b['type'];
                $source_equal = $a['source'] === $b['source'];
                if (
                    $url_equal && $message_equal
                    && $type_equal && $source_equal
                ) {
                    unset($a_array[$key]);
                }

            }
        }

        // We need to reset the array keys before we
        // return them.
        return array_values($a_array);

    }

    // New alerts that have existing alerts are removed.
    $new_alerts = remove_duplicates(
        $queued_alerts, $existing_alerts
    );

    // Let's add the new alerts.
    if(!empty($new_alerts)){
        DataAccess::add_db_rows(
            'alerts', $new_alerts
        );
    }

    // Equalified alerts are existing alerts that don't
    // have new alert duplicates.
    $equalified_alerts = remove_duplicates(
        $existing_alerts, $queued_alerts
    );


    // Let's deal with equalifed alerts.
    if(!empty($equalified_alerts)){

        // We must remove alerts that have already been
        // equalified.
        foreach($equalified_alerts as $key => $alert){
            if (
                $alert['status'] == 'equalified'
            ) {
                unset($equalified_alerts[$key]);
            }
        }

        // We'll update their status to 'equalified'.
        $fields_updated = array(
            array(
                'name'  => 'status',
                'value' => 'equalified'
            )
        );

        // Let's build a way to filter by IDs.
        $filtered_by_ids = [];
        foreach($equalified_alerts as $alert){
            array_push($filtered_by_ids, array(
                'name' => 'id',
                'value'=> $alert['id']
            ));
        }
        if(!empty($filtered_by_ids)){
            DataAccess::update_db_rows(
                'alerts', $fields_updated, $filtered_by_ids, 'OR'
            );    
        }

    }

    // Let's log our process for the CLI.
    $alerts_updated = 
        count($equalified_alerts)+count($new_alerts);
    $alerts_processed = 
        count($queued_alerts)+count($existing_alerts);
    update_scan_log("\n>>> Updated $alerts_updated of $alerts_processed processed alerts.\n");

    // Finally, we'll return a list of sites we processed.
    return $integration_output['processed_sites'];

}