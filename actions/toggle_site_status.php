<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

// Get URL variables and fallbacks.
$site = $_GET['site'];
if(empty($site))
    throw new Exception('Site is not specified');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for "'.$site.'"');

// Toggle site status.
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
header('Location: ../index.php?view=sites&status=success');