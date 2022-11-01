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

    // Let's log our process for the CLI.
    update_scan_log("\n\n\n> Processing alerts...");
    $time_pre = microtime(true);

    // From the previous process, we should have
    // the following data that we'll use.
    $processed_urls = $integration_output[
        'processed_urls'
    ];
    $processed_sites = $integration_output[
        'processed_sites'
    ];

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Now let's get our existing alerts, filtered to the
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
        'alerts', $existing_alert_filters, 1, 1000000
    )['content'];
    if(empty($existing_alerts))
        $existing_alerts = array();

    // Let's find equalified alerts.
    $equalified_alerts =  DataAccess::get_joined_db(
        'equalified_alerts', $processed_sites
    );

    // Let's mark the status of these alerts "Equalified".
    if(!empty($equalified_alerts)){

        // We need to create filters to equalify.
        $filters = [];
        foreach($equalified_alerts as $alert){
            $new_filter = array(
                'name' => 'id',
                'value' => $alert->id,
                'condition' => 'OR'
            );
            array_push($filters, $new_filter);
        };

        // Now let's update the rows in alerts.
        $fields = array(
            array(
                'name' => 'status',
                'value' => 'equalified'
            )
        );
        DataAccess::update_db_rows(
            'alerts', $fields, $filters
        );

        // And let's remove equalified alerts from the queue.
        DataAccess::delete_db_entries(
            'queued_alerts', $filters
        );

    };

    // Let's find new alerts from queued alerts.
    $new_alerts =  DataAccess::get_joined_db(
        'new_alerts'
    );

    // Let's move new alerts into the alerts table.
    if(!empty($new_alerts)){

        // We need to update fields to add.
        $rows = [];
        foreach($new_alerts as $alert){
            $new_row = array(
                'url'       => $alert->url,
                'message'   => $alert->message,
                'status'    => $alert->status,
                'site_id'   => $alert->site_id,
                'tags'      => $alert->tags,
                'source'    => $alert->source
            );
            array_push($rows, $new_row);
        };

        // Now let's update the rows in alerts.
        DataAccess::add_db_rows(
            'alerts', $rows
        );

    };

    // Finally, we clear the queued alerts table.
    DataAccess::delete_db_entries('queued_alerts');
    
    // Let's log the total alerts.
    $equalified_count = number_format(count($equalified_alerts));
    $new_count = number_format(count($new_alerts));
    update_scan_log("\n>>> $equalified_count alert(s) equalified and $new_count new alert(s).");

    // Let's also log the time it took.
    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;
    update_scan_log(
        "\n>>> Completed process in $exec_time seconds\n"
    );

    // Finally, we'll return a list of sites we processed.
    return $integration_output['processed_sites'];

}