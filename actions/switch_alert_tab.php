<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Setup variables.
$updated_current_tab = $_GET['alert_tab'];

// Get existing meta.
$alert_tabs = unserialize(DataAccess::get_meta_value('alert_tabs'));

// Set 'view_id' if none exists.
$alert_tabs['current_tab'] = $updated_current_tab;

// Save view data with data that MySQL understands.
$serialized_alert_tabs = serialize($alert_tabs);
DataAccess::update_meta_value('alert_tabs', $serialized_alert_tabs);

// Reload alerts page.
header('Location: ../index.php?view=alerts');