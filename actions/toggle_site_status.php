<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document controls everything around site status. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
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
        'Status is not specified for "'.$site.'"'
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

    // Now we can archive alerts.
    update_alerts(1, $site_id);

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

    // Now we can unarchive alerts.
    update_alerts(0, $site_id);

}


// Redirect
header('Location: ../index.php?view=sites');

/**
 * Update Alerts
 * @param string newstatus
 * @param string site_id
 */
function update_alerts($new_status, $site_id) {

    // Get active inteagartions.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );

    // Create active sites to alerts filter.
    $integration_uris = NULL;
    if(!empty($active_integrations)){
        $integration_uris = array();
        foreach ($active_integrations as $uri){
            array_push(
                $integration_uris,
                array(
                    'name' => 'source',
                    'value' => $uri,
                    'operator' => '=',
                    'condition' => 'OR'
                )
            );
        }
    }

    // Create filter to select alerts with the current 
    // integration and active sites.
    $alerts_filters = array(
        array(
            'name' => 'site_id',
            'value' => $site_id 
        ),
        array(
            'name' => 'source',
            'value' => $integration_uris
        )
    );

    // Now let's update fields.
    $updated_fields = array(
        array(
            'name' => 'archived',
            'value' => $new_status
        )
    );
    DataAccess::update_db_rows(
        'alerts', $updated_fields, $alerts_filters
    );

}