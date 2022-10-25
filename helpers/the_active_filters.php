<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document outputs active sites and integrations.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * The Active Filters
 */
function the_active_filters(){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Now let's get active integrations.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );

    // We also need active sites.
    $filtered_to_active_sites = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_sites = DataAccess::get_db_rows( 'sites',
        $filtered_to_active_sites, 1, 10000
    )['content'];

    // Let's generate an array for sites and integrations.
    $output = array();
    if(!empty($active_integrations)){
        foreach($active_integrations as $integration){
            array_push(
                $output,
                array(
                    'name' => 'source',
                    'value' => $integration
                )
            );
        }
    }
    if(!empty($active_sites)){
        foreach($active_sites as $site){
            array_push(
                $output,
                array(
                    'name' => 'site_id',
                    'value' => $site->id
                )
            );
        }
    }

    // Return the output
    return $output;

}