<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL variables and fallbacks.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for page "'.$id.'"');

// Toggle page status.
$site = get_page($db, $id)->site;
if($old_status == 'active'){
    update_site_status($db, $site, 'archived');

    // Alerts are deleted when a page is archived.
    $filters = [
        array(
            'name'  => 'site',
            'value' => $site
        )
    ];
    delete_alerts($db, $filters);
    
}
if($old_status == 'archived'){
    update_site_status($db, $site, 'active');
}

// Redirect
header('Location: ../index.php?view=site_details&id='.$id.'&status=success');