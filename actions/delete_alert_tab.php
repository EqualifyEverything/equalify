<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Setup variables.
$alert_tab = $_GET['tab'];

// Get existing meta.
$alert_tabs = unserialize(DataAccess::get_meta_value('alert_tabs'));

// Remove the tab.
unset($alert_tabs['tabs'][$alert_tab]);

// Change the current tab to the first tab,
// which can never be deleted.
$alert_tabs['current_tab'] = 1;

// Save view data with data that MySQL understands.
$serialized_alert_tabs = serialize($alert_tabs);
DataAccess::update_meta_value('alert_tabs', $serialized_alert_tabs);

// Reload alerts page.
header('Location: ../index.php?view=alerts');