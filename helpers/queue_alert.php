<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document includes a function to help users add 
 * alerts
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Add Alert
 * @param array attributes ['source' => '', 'url' => '', 
 * 'type' => '', 'message' => '', 'meta' => '']
 */
function queue_alert(array $attributes){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Now lets start work by setting required attributes.
    if(empty($attributes['source']))
        throw new Exception('Source is missing', 1);
    if(empty($attributes['type']))
        throw new Exception('Type is missing', 1);

    // Let's set fallbacks for non-required sources so
    // that we can refer to the attributes, no matter if
    // they are empty or not.
    if(empty($attributes['url']))
        $attributes['url'] = '';
    if(empty($attributes['message']))
        $attributes['message'] = '';
    if(empty($attributes['meta']))
        $attributes['meta'] = '';

    // Let's validate the variables to make sure they
    // include allowed data.
    $allowed_types = array(
        'error', 'warning', 'notice'
    );
    if(!in_array($attributes['type'], $allowed_types))
        throw new Exception(
            'Alert type "'.$attributes['type'].'" is invlaid'
        );

    // We should also sanitize the message to a format
    // ready for the DB.
    $attributes['message'] = htmlspecialchars(
        $attributes['message'], ENT_NOQUOTES
    );
    if(is_array($attributes['meta'])){
        $attributes['meta'] = htmlspecialchars(
            serialize($attributes['meta']), ENT_NOQUOTES
        );
    }

    // Let's setup attributes in a usable way for the DB.
    $alert_arguments = array(
        array(

            // Where is the alert being reported by? We
            // often use the integration URI or 'system'
            // for Equalify-created alerts.
            'name' => 'source',
            'value'=> $attributes['source']

        ),
        array(

            // What URL is the report related to?
            'name' => 'url',
            'value'=> $attributes['url']

        ),
        array(

            // What type of alert is this?
            'name' => 'type',
            'value'=> $attributes['type']

        ),
        array(

            // Any message to include for the user?
            'name' => 'message',
            'value'=> $attributes['message']
            
        ),
        array(

            // What else? You can add arrays if you
            // want.
            'name' => 'meta',
            'value'=> $attributes['meta']

        )
    );

    // Time to get exsiting alerts, so we're not posting
    // duplicate alerts.
    $existing_alerts = DataAccess::get_db_entries(
        'alerts', $alert_arguments
    )['content'];

    // We can now add the status of active, since every 
    // new alert should be active.
    array_push( $alert_arguments, 
        array(
            'name' => 'status',
            'value'=> 'active'
        )
    );

    // Now let's update or add the alert, depending on 
    // if a similar alert already exists.x
    if(!empty($existing_alerts)){

        // All alerts with the same data will need to be
        // updated.
        foreach($existing_alerts as $alert){

            // Updating the alert's status will also
            // update its timestamp. 
            DataAccess::update_db_entry( 
                'alerts', $alert->id, $alert_arguments
            );

        }

    }else{
        
        // Lets add an active alert, since it doesn't 
        // already exists.
        DataAccess::add_db_entry( 
            'alerts', $alert_arguments
        );

    }

}