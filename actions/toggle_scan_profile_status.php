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
$property_id = $_GET['id'];
if(empty($property_id))
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
            'value'=> $property_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'archived'
        )
    );
    DataAccess::update_db_rows(
        'properties', $fields_to_update, $filtered_to_id
    );

    // Now we can archive notices.
    update_notices(1, $property_id);

}
if($old_status == 'archived'){

    // Change site status
    $filtered_to_id = array(
        array(
            'name' => 'id',
            'value'=> $property_id
        )
    );
    $fields_to_update = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    DataAccess::update_db_rows(
        'properties', $fields_to_update, $filtered_to_id
    );

    // Now we can unarchive notices.
    update_notices(0, $property_id);

}


// Redirect
header('Location: ../index.php?view=properties');

/**
 * Update Notices
 * @param string newstatus
 * @param string property_id
 */
function update_notices($new_status, $property_id) {

    // Get active inteagartions.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );

    // Create active properties to notices filter.
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

    // Create filter to select notices with the current 
    // integration and active properties.
    $notices_filters = array(
        array(
            'name' => 'property_id',
            'value' => $property_id 
        ),
        array(
            'name' => 'source',
            'value' => $integration_uris
        )
    );

    // Now let's update fields.
    $updated_notice_fields = array(
        array(
            'name' => 'archived',
            'value' => $new_status
        )
    );
    DataAccess::update_db_rows(
        'notices', $updated_notice_fields, $notices_filters
    );

}