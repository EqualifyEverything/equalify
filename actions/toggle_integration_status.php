<?php
// Add DB info and required functions.
require_once '../config.php';
require_once '../models/integrations.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

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
    delete_alerts($db, $alerts_filter);

    // Status text.
    $new_status = 'Disabled';

}elseif($old_status == 'Disabled'){

    // Setup fields.
    $integration_fields = get_integration_fields($uri);
    if( !empty($integration_fields['db']) ){
        $integration_db_fields = $integration_fields['db'];

        // Setup "accounts" fields.
        if( !empty($integration_db_fields['accounts']) ){
            foreach($integration_db_fields['accounts'] as $integration_accounts_field){
                if(!db_column_exists($db, 'accounts', $integration_accounts_field['name']))
                    add_db_column($db, 'accounts', $integration_accounts_field['name'], $integration_accounts_field['type']);
            }
        }

        // Setup "properties" fields.
        if( !empty($integration_db_fields['properties']) ){
            foreach($integration_db_fields['properties'] as $integration_properties_field){
                if(!db_column_exists($db, 'properties', $integration_properties_field['name']))
                    add_db_column($db, 'properties', $integration_properties_field['name'], $integration_properties_field['type']);
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