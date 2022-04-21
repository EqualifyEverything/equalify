<?php
// Add DB info and required functions.
require_once '../config.php';
require_once '../models/integrations.php';
require_once '../models/db.php';

// Get URL parameters.
$uri = $_GET['uri'];
if(empty($uri))
    throw new Exception('Integration is not specfied');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for integration "'.$uri.'"');

// Integration file is going to be updated.
$integration_path = '../integrations/'.$uri.'/functions.php';
$integration_contents = file_get_contents($integration_path);

// Conditional operations based on status:
if($old_status == 'Active'){

    // Remove integration-related alerts.
    $alerts_filter = [
        array(
            'name'   =>  'integration_uri',
            'value'  =>  $uri
        )
    ];
    DataAccess::delete_alerts($alerts_filter);

    // Remove fields.
    $integration_fields = get_integration_fields($uri);
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Delete "meta" fields.
        if( !empty($integration_db_fields['meta']) ){
            foreach($integration_db_fields['meta'] as $integration_meta_field){
                if(DataAccess::db_column_exists('meta', $integration_meta_field['name']))
                    DataAccess::delete_db_column('meta', $integration_meta_field['name']);
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

    // Status text.
    $new_status = 'Disabled';

}elseif($old_status == 'Disabled'){

    // Setup fields.
    $integration_fields = get_integration_fields($uri);
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Setup "meta" fields.
        if( !empty($integration_db_fields['meta']) ){
            foreach($integration_db_fields['meta'] as $integration_meta_field){
                if(!DataAccess::db_column_exists('meta', $integration_meta_field['name']))
                    DataAccess::add_db_column('meta', $integration_meta_field['name'], $integration_meta_field['type']);
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

    // Status text.
    $new_status = 'Active';

}else{
    throw new Exception('The status,"'.$old_status.'," is not allowed');
}

// Replace the file contents.
$new_contents = str_replace($old_status, $new_status, $integration_contents);
file_put_contents($integration_path, $new_contents);

// Redirect.
header('Location: ../index.php?view=integrations&id='.$uri.'&status=success');