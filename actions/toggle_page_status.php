<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

// Get URL variables and fallbacks.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for page "'.$id.'"');

// Toggle page status.
$site = DataAccess::get_page($id)->site;
if($old_status == 'active'){
    DataAccess::update_site_status($site, 'archived');

    // Alerts are deleted when a page is archived.
    $filters = [
        array(
            'name'  => 'site',
            'value' => $site
        )
    ];
    DataAccess::delete_alerts($filters);
    
}
if($old_status == 'archived'){
    DataAccess::update_site_status($site, 'active');
}

// Redirect
header('Location: ../index.php?view=site_details&id='.$id.'&status=success');