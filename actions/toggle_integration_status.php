<?php
// Add DB info and required functions.
require_once '../config.php';
require_once '../models/integrations.php';
require_once '../models/db.php';

// Get URL parameters.
$integration_uri = $_GET['uri'];
if(empty($integration_uri))
    throw new Exception('Integration is not specfied');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Old status is not specfied for integration "'.$integration_uri.'"');

// Conditional operations based on status:
if($old_status == 'Active'){

    // Remove fields.
    $integration_fields = get_integration_fields($integration_uri);
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Delete "meta" fields.
        if( !empty($integration_db_fields['meta']) ){
            foreach($integration_db_fields['meta'] as $integration_meta_field){
                if(!DataAccess::get_meta_value($integration_meta_field['name']))
                    DataAccess::delete_meta($integration_meta_field['name']);
            }
        }

        // Delete "pages" fields.
        if( !empty($integration_db_fields['pages']) ){
            foreach($integration_db_fields['pages'] as $integration_pages_field){
                if(DataAccess::db_column_exists('pages', $integration_pages_field['name']))
                    DataAccess::delete_db_column('pages', $integration_pages_field['name']);
            }
        }

    }

    // Remove from "active_integrations" meta field.
    $active_integrations = unserialize(DataAccess::get_meta_value('active_integrations'));
    if (($key = array_search($integration_uri, $active_integrations)) !== false) {
        unset($active_integrations[$key]);
        $new_active_integrations = serialize($active_integrations);
        DataAccess::update_meta_value('active_integrations', $new_active_integrations);
    }

}elseif($old_status == 'Disabled'){

    // Setup fields.
    $integration_fields = get_integration_fields($integration_uri);
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Setup "meta" fields.
        if( !empty($integration_db_fields['meta']) ){
            foreach($integration_db_fields['meta'] as $integration_meta_field){
                if(!DataAccess::get_meta_value($integration_meta_field['name']))
                    DataAccess::add_meta($integration_meta_field['name'], $integration_meta_field['value']);
            }
        }

        // Setup "pages" fields.
        if( !empty($integration_db_fields['pages']) ){
            foreach($integration_db_fields['pages'] as $integration_pages_field){
                if(!DataAccess::db_column_exists('pages', $integration_pages_field['name']))
                    DataAccess::add_db_column('pages', $integration_pages_field['name'], $integration_pages_field['type']);
            }
        }

    }

    // Add to "active_integrations" meta field.
    $active_integrations = unserialize(DataAccess::get_meta_value('active_integrations'));
    array_push( $active_integrations, $integration_uri);
    $new_active_integrations = serialize($active_integrations);
    DataAccess::update_meta_value('active_integrations', $new_active_integrations);

}else{
    throw new Exception('The status,"'.$old_status.'," is not allowed');
}

// Redirect.
header('Location: ../index.php?view=integrations&id='.$integration_uri);