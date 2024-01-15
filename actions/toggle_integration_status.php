<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document controls everything around integration 
 * status.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Add DB info and required functions.
require_once '../config.php';
require_once '../models/integrations.php';
require_once '../models/db.php';
require_once '../helpers/register_tags.php';
require_once '../helpers/delete_tags.php';

// Get URL parameters.
$integration_uri = $_GET['uri'];
if(empty($integration_uri))
    throw new Exception('Integration is not specified');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception(
        'Old status is not specified for integration 
        "'.$integration_uri.'"'
    );

// Conditional operations based on status:
if($old_status == 'Active'){

    // Remove fields.
    $integration_fields = get_integration_fields(
        $integration_uri
    );
    if (!empty($integration_fields['db'])){
        $integration_db_fields = $integration_fields['db'];

        // Delete "meta" fields.
        if (!empty($integration_db_fields['meta']) ){
            foreach(
                $integration_db_fields['meta'] 
                as $integration_meta_field
            ){
                if (!DataAccess::get_meta_value($integration_meta_field['name'])) {
                    DataAccess::delete_meta(
                        $integration_meta_field['name']
                    );
                }
            }
        }
        
    }

    // Remove tags.
    $integration_tags = get_integration_tags(
        $integration_uri
    );
    if( !empty($integration_tags) ){
        delete_tags($integration_tags);
    }
    
    // Remove from "active_integrations" meta field.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );
    if (($key = array_search(
            $integration_uri, $active_integrations
        )) !== false) 
    {
        unset($active_integrations[$key]);
        $new_active_integrations = serialize(
            $active_integrations
        );
        DataAccess::update_meta_value(
            'active_integrations', $new_active_integrations
        );
    }

    // Now we can archive alerts.
    update_alerts(1, $integration_uri);


}elseif($old_status == 'Disabled'){

    // Set up fields.
    $integration_fields = get_integration_fields(
        $integration_uri
    );
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Set up "meta" fields.
        if( !empty($integration_db_fields['meta']) ){
            foreach(
                $integration_db_fields['meta']
                as $integration_meta_field
            ){
                // prevent checking empty strings for integrations
                if (empty(DataAccess::get_meta_value($integration_meta_field['name'], true))) {
                    DataAccess::add_meta(
                        $integration_meta_field['name'], 
                        $integration_meta_field['value']
                    );
                }
            }
        }

        // Set up "pages" fields.
        if( !empty($integration_db_fields['pages']) ){
            foreach(
                $integration_db_fields['pages'] 
                as $integration_pages_field
            ){
                if(!DataAccess::db_column_exists( 'pages', $integration_pages_field['name'] )) {
                    DataAccess::add_db_column(
                        'pages', $integration_pages_field['name'], 
                        $integration_pages_field['type']
                    );
                }
            }
        }

    }

    // Register tags.
    $integration_tags = get_integration_tags(
        $integration_uri
    );
    if( !empty($integration_tags) ){
        register_tags($integration_tags);
    }

    // Add to "active_integrations" meta field.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );
    array_push( $active_integrations, $integration_uri);
    $new_active_integrations = serialize($active_integrations);
    DataAccess::update_meta_value(
        'active_integrations', $new_active_integrations
    );

    // Now we can unarchive alerts.
    update_alerts(0, $integration_uri);

}else{
    throw new Exception(
        'The status,"'.$old_status.'," is not allowed'
    );
}

// Redirect.
header(
    'Location: ../index.php?view=integrations&id='.$integration_uri
);

/**
 * Update Alerts
 * @param string newstatus
 * @param string integration_uri
 */
function update_alerts($new_status, $integration_uri) {

    // Get active scan profiles.
    $scan_profiles_filter = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_scan_profiles = DataAccess::get_db_rows(
        'scan_profiles', $scan_profiles_filter, 1, 10000
    );

    // Create active scan profiles to alerts filter.
    $scan_profile_ids = NULL;
    if(!empty($active_scan_profiles['content'])){
        $scan_profile_ids = array();
        foreach ($active_scan_profiles['content'] as $scan_profile){
            array_push(
                $scan_profile_ids,
                array(
                    'name' => 'site_id',
                    'value' => $scan_profile->id,
                    'operator' => '=',
                    'condition' => 'OR'
                )
            );
        }
    }

    // Create filter to select alerts with the current 
    // integration and active scan profiles.
    $alerts_filters = array(
        array(
            'name' => 'source',
            'value' => $integration_uri 
        ),
        array(
            'name' => 'site_id',
            'value' => $scan_profile_ids
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