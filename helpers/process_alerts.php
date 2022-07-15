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
 */
function process_alerts(
    array $queued_alerts, array $process_info
){

    // Let's log our process for the CLI.
    echo "\n\n\n> Processing alerts...";

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Now lets get our existing alerts, filtered to the
    // pages we're interested in.
    $existing_alert_filters = [];
    foreach ( $process_info['urls'] as $url){
        array_push($existing_alert_filters, array(
            'name'     => 'url',
            'value'    => $url
        ));
    };
    $existing_alerts = DataAccess::get_db_rows(
        'alerts', $existing_alert_filters, 1, 10000, 'OR'
    )['content'];

    // Let's further filter existing alerts.
    foreach (
        $existing_alerts as $key => $existing_alert
    ) {

        // We only include alerts with queued sources.
        if(!in_array(
            $existing_alert->source, 
            $process_info['sources']
        ))
            unset($existing_alerts[$key]);
            
    }

    // We'll now seperate duplicate alerts.
    $duplicate_alerts = [];
    foreach(
        $queued_alerts as 
        $queued_key => $queued_alert
    ):

        // We compare queued alerts to existing alerts.
        foreach(
            $existing_alerts as 
            $key => $existing_alert
        ){

            // Every attribute except status, time' is
            // compared.
            if(
                (
                    $queued_alert['source'] 
                    == $existing_alert->source
                )
                && (
                    $queued_alert['url'] 
                    == $existing_alert->url
                )
                && (
                    $queued_alert['type'] 
                    == $existing_alert->type
                )
                && (
                    $queued_alert['message'] 
                    == $existing_alert->message
                )
                && (
                    $queued_alert['meta'] 
                    == $existing_alert->meta
                )
            ){
                
                // Move duplicates away from existing alerts.
                array_push(
                    $duplicate_alerts, $existing_alert
                );
                unset($existing_alerts[$key]);

            }

        }

        // Now we need to move duplicates from queued alerts.
        foreach ($duplicate_alerts as $duplicate_alert){

            // We don't compare time, id, and status.
            unset($duplicate_alert->id);
            unset($duplicate_alert->time);
            unset($duplicate_alert->status);

            // Let's also convert the current obj into an array.
            $duplicate_alert_array = (array) $duplicate_alert;

            // Duplicates will have id, time, and status which
            // we aren't comparing.
            if( 
                array_diff_assoc(
                    $duplicate_alert_array, $queued_alert
                ) == array()
            )
                unset($queued_alerts[$queued_key]);

        }

    endforeach;

    // Now we can deal with any existing alerts.
    if(!empty($existing_alerts)){

        // We need to update their status to 'equalified'.
        $fields_updated = array(
            array(
                'name'  => 'status',
                'value' => 'equalified'
            )
        );

        // Let's build a way to filter by IDs.
        $filterd_by_ids = [];
        foreach($existing_alerts as $alert){
            array_push($filterd_by_ids, array(
                'name' => 'id',
                'value'=> $alert->id
            ));
        }
        DataAccess::update_db_rows(
            'alerts', $fields_updated, $filterd_by_ids, 'OR'
        );

    }

    // Any existing queued alerts are new alerts.
    if(!empty($queued_alerts)){
        DataAccess::add_db_rows(
            'alerts', $queued_alerts
        );
    }

    // Finally, Let's log our process for the CLI.
    $alert_counts = 
        count($duplicate_alerts)+count($existing_alerts)
        +count($queued_alerts);
    echo "\n> Processed $alert_counts alerts.";

}