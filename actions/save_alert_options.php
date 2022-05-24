<?php
// Info on DB must be declared to use db.php models.
require_once 'models/db.php';

// Get existing meta.
$alert_options = DataAccess::get_meta_value('alert_options');

// If alert view doesn't exist in meta, set it up.
if(empty($alert_options)){
    $alert_options = array(
        'current_view' => '',
        'views'  => ''
    );
}

// Update alert content
$_POST['alert_view_name'] = array(
    'filters' => array(
        1 => array(
            'name'  => 'integrations',
            'value' => $_POST['alert_integrations']
        ),
        2 => array(
            'name' => 'types',
            'value' => $_POST['alert_types']
        ),
        3 => array(
            'name' => 'sources',
            'value' => $_POST['alert_sources']
        ),
        4 => array(
            'name' => 'little_forrest_guidelines',
            'value' => $_POST['little_forrest_guidelines']
        ),
        5 => array(
            'name' => 'little_forrest_tags',
            'value' => $_POST['little_forrest_tags']
        )
    ),
    'name' => $_POST['alert_view_name']
);
$alert_options['current_view'] = $_POST['alert_view_name'];
array_push($alert_options['views'], $_POST['alert_view_name']);
DataAccess::update_meta('alert_options', $alert_options);

// Reload alerts page.
header('Location: ../index.php?view=alerts');
?>