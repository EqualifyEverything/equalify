<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document controls everything around site status. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

// Get URL variables and fallbacks.
$site_id = $_GET['id'];
if(empty($site_id))
    throw new Exception('Site is not specified');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception(
        'Status is not specfied for "'.$site.'"'
    );

// Toggle site status.
if($old_status == 'active'){

    // Change site status
    $filtered_to_id = array(
        array(
            'name' => 'id',
            'value'=> $site_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'archived'
        )
    );
    DataAccess::update_db_rows(
        'sites', $fields_to_update, $filtered_to_id
    );

    // Archive alerts.
    $filtered_to_site = array(
        array(
            'name' => 'site_id',
            'value' => $site_id,
        )
    );
    $updated_fields = array(
        array(
            'name' => 'archived',
            'value' => 1
        )
    );
    DataAccess::update_db_rows(
        'alerts', $updated_fields, $filtered_to_site
    );

}
if($old_status == 'archived'){

    // Change site status
    $filtered_to_id = array(
        array(
            'name' => 'id',
            'value'=> $site_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    DataAccess::update_db_rows(
        'sites', $fields_to_update, $filtered_to_id
    );

    // Activate alerts.
    $filtered_to_site = array(
        array(
            'name' => 'site_id',
            'value' => $site_id,
        )
    );
    $updated_fields = array(
        array(
            'name' => 'archived',
            'value' => 0
        )
    );
    DataAccess::update_db_rows(
        'alerts', $updated_fields, $filtered_to_site
    );

}

// Redirect
header('Location: ../index.php?view=sites');