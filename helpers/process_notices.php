<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This doc deals with the notices in a process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Notices
 * @param array integration_output
 */
function process_notices( array $integration_output) {

    // Let's log our process for the CLI.
    update_scan_log("\n\n\n> Processing notices...");
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

    // Now let's get our existing notices, filtered to the
    // pages we're interested in.
    $urls_to_filter = array();
    foreach ( $processed_urls as $url){
        array_push($urls_to_filter, array(
            'name'     => 'url',
            'value'    => $url,
            'condition'=> 'OR'
        ));
    };
    $existing_notice_filters = array(
        array(
            'name'  => 'archived',
            'value' => 0
        ),
        array(
            'name'  => 'url',
            'value' => $urls_to_filter
        )
    );
    $existing_notices = DataAccess::get_db_rows(
        'notices', $existing_notice_filters, 1, 1000000
    )['content'];
    if(empty($existing_notices))
        $existing_notices = array();

    // Let's find equalified notices.
    $equalified_notices =  DataAccess::get_joined_db(
        'equalified_notices', $processed_sites
    );

    // Let's mark the status of these notices "Equalified".
    if(!empty($equalified_notices)){

        // We need to create filters to equalify.
        $filters = [];
        foreach($equalified_notices as $notice){
            $new_filter = array(
                'name' => 'id',
                'value' => $notice->id,
                'condition' => 'OR'
            );
            array_push($filters, $new_filter);
        };

        // Now let's update the rows in notices.
        $fields = array(
            array(
                'name' => 'status',
                'value' => 'equalified'
            )
        );
        DataAccess::update_db_rows(
            'notices', $fields, $filters
        );

        // And let's remove equalified notices from the queue.
        DataAccess::delete_db_entries(
            'queued_notices', $filters
        );

    };

    // Let's find new notices from queued notices.
    $new_notices =  DataAccess::get_joined_db(
        'new_notices'
    );

    // Let's move new notices into the notices table.
    if(!empty($new_notices)){

        // We need to update fields to add.
        $rows = [];
        foreach($new_notices as $notice){
            $new_row = array(
                'related_url'       => $notice->url,
                'message'   => $notice->message,
                'status'    => $notice->status,
                'property_id'   => $notice->property_id,
                'tags'      => $notice->tags,
                'source'    => $notice->source,
                'meta' => $notice->meta
            );
            array_push($rows, $new_row);
        };

        // Now let's update the rows in notices.
        DataAccess::add_db_rows(
            'notices', $rows
        );

    };

    // Finally, we clear the queued notices table.
    DataAccess::delete_db_entries('queued_notices');
    
    // Let's log the total notices.
    $equalified_count = number_format(count($equalified_notices));
    $new_count = number_format(count($new_notices));
    update_scan_log("\n>>> $equalified_count notice(s) equalified and $new_count new notice(s).");

    // Let's also log the time it took.
    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;
    update_scan_log(
        "\n>>> Completed process in $exec_time seconds\n"
    );

    // Finally, we'll return a list of sites we processed.
    return $integration_output['processed_sites'];

}