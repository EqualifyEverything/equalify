<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * These functions create a mark alerts as equalifed.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Alerts
 */
function process_alerts(array $queued_alerts){

    // Let's log our process for the CLI.
    echo "\n\n\n> Processing alerts...";

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // We'll need to get a list of the urls and sources
    // included in the queued alerts, so we can limit
    // the amount of exisitng alerts we compare.
    $urls_queued = [];
    $sources_queued = [];
    foreach ($queued_alerts as $alert){
        if(!in_array($alert['url'], $urls_queued))
            array_push($urls_queued, $alert['url']);
        if(!in_array($alert['source'], $sources_queued))
            array_push($sources_queued, $alert['source']);
    }

    // Now lets get our existing alerts, filtered to the
    // pages we're interested in.
    $existing_alert_filters = [];
    foreach ( $urls_queued as $url){
        array_push($existing_alert_filters, array(
            'name'     => 'url',
            'value'    => $url
        ));
    };
    $existing_alerts = DataAccess::get_db_rows(
        'alerts', $existing_alert_filters, 1, 10000, 'OR'
    )['content'];

    // Let's further filter existing alerts by only 
    // including alerts with queued sources.
    foreach (
        $existing_alerts as $key => $existing_alert
    ) {
        if(!in_array(
            $existing_alert->source, $sources_queued
        ))
            unset($existing_alerts[$key]);
    }

    // We'll now seperate duplicate alerts.
    $duplicate_alerts = [];
    foreach(
        $queued_alerts as 
        $key => $queued_alert
    ):

        // We compare queued alerts to existing alerts.
        foreach(
            $existing_alerts as 
            $key => $existing_alert
        ){

            // Every attribute except 'status' and 'time' is
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
                
                // We move duplicates to updated_alerts.
                array_push($duplicate_alerts, $existing_alert);
                unset($existing_alerts[$key]);
                unset($queued_alerts[$key]);

            }

        }

    endforeach;

    // Let's update the duplicate alerts in the db so we have an
    // updated timestamp.
    if(!empty($duplicate_alerts)){
        DataAccess::update_db_rows(
            'alerts', $duplicate_fields, $duplicate_filters
        );
    }

    // Now we know that any remaining existing alerts can be
    // marked as 'equalified'.
    if(!empty($equalified_alerts)){
        DataAccess::update_db_rows(
            'alerts', $equalified_fields, $equalified_filters
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