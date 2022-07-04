<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Get existing meta.
$alert_tabs = DataAccess::get_meta_value('alert_tabs');

// If "alert_tabs" doesn't exist in meta, set it up.
if(empty($alert_tabs)){
    $alert_tabs = array(
        'current_tab' => '',
        'tabs'  => array()
    );
    DataAccess::add_meta('alert_tabs', $alert_tabs);
}else{

    // We need to unserialize the meta.
    $alert_tabs = unserialize($alert_tabs);

}

// Set 'tab_id' if none exists.
if(empty($_POST['tab_id'])){

    // New ids are 1 + the number of exsting alerts.
    $number_of_tabs = count($alert_tabs['tabs']);
    $tab_id = $number_of_tabs+1;

    // We don't need a success message for new tabs
    // since the creation of a tab is success enoguh.
    $success_parameter = '';

}else{
    $tab_id = $_POST['tab_id'];

    // Success messages are added to updated tabs since
    // there would be no other way of notifying users 
    // if updated filters changed nothing.
    $success_parameter = '&success=true';

}

// Set 'current_tab' to tab that's being added/updated.
$alert_tabs['current_tab'] = $tab_id;

// Setup data for tab that will be updated.
$updated_tab = array(
    $tab_id => array(
        'id'      => $tab_id,
        'name'    => $_POST['tab_name'],
        'filters' => array()
    ),
);

// Add filters.
if(!empty($_POST['integration_uri'])){
    $integration_uri_filter = array(
        'name'  => 'integration_uri',
        'value' => $_POST['integration_uri']
    );
    array_push($updated_tab[$tab_id]['filters'], $integration_uri_filter );    
}
if(!empty($_POST['type'])){
    $type_filter = array(
        'name' => 'type',
        'value' => $_POST['type']
    );
    array_push($updated_tab[$tab_id]['filters'], $type_filter );    
}
if(!empty($_POST['source'])){
    $source_filter = array(
        'name' => 'source',
        'value' => $_POST['source']
    );
    array_push($updated_tab[$tab_id]['filters'], $source_filter );    
}

// Add updated data to tabs.
$alert_tabs['tabs'][$tab_id] = $updated_tab[$tab_id];

// Save tab data with data that MySQL understands.
$serialized_alert_tabs = serialize($alert_tabs);
DataAccess::update_meta_value('alert_tabs', $serialized_alert_tabs);

// Reload alerts page with optional success notice
// we set above
header('Location: ../index.php?view=alerts'.$success_parameter);
?>