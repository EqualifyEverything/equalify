<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Get existing meta.
$alert_options = DataAccess::get_meta_value('alert_options');

// If "alert_view" doesn't exist in meta, set it up.
if(empty($alert_options)){
    $alert_options = array(
        'current_view' => '',
        'views'  => array()
    );
    DataAccess::add_meta('alert_options', $alert_options);
}else{

    // We need to unserialize the meta.
    $alert_options = unserialize($alert_options);

}

// Set 'current_view' to view that's being added/updated.
$alert_options['current_view'] = $_POST['view_name'];

// Setup data for view that will be updated.
$updated_view = array(
    $_POST['view_name'] => array(
        'filters' => array(),
        'name' => $_POST['view_name']
    ),
);

// Add filters.
if(!empty($_POST['integration_uri'])){
    $integration_uri_filter = array(
        'name'  => 'integration_uri',
        'value' => $_POST['integration_uri']
    );
    array_push($updated_view[$_POST['view_name']]['filters'], $integration_uri_filter );    
}
if(!empty($_POST['type'])){
    $type_filter = array(
        'name' => 'type',
        'value' => $_POST['type']
    );
    array_push($updated_view[$_POST['view_name']]['filters'], $type_filter );    
}
if(!empty($_POST['source'])){
    $source_filter = array(
        'name' => 'source',
        'value' => $_POST['source']
    );
    array_push($updated_view[$_POST['view_name']]['filters'], $source_filter );    
}

// Add updated data to views.
$alert_options['views'][$_POST['view_name']] = $updated_view[$_POST['view_name']];  

// Remove existing view.
if(!empty($_POST['existing_view']))
    unset($alert_options['views'][$_POST['existing_view']]);    

// Save view data.
DataAccess::update_meta_value('alert_options', $alert_options);

// Reload alerts page.
header('Location: ../index.php?view=alerts&success=true');
?>