<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document deletes labels.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Setup variables.
$alert_label = $_GET['id'];

// Get existing meta.
$alert_labels = unserialize(
    DataAccess::get_meta_value('alert_labels')
);

// Remove the label.
unset($alert_labels['labels'][$alert_label]);

// Change the current label to the first label, which can 
// never be deleted.
$alert_labels['current_label'] = 1;

// Save view data with data that MySQL understands.
$serialized_alert_labels = serialize($alert_labels);
DataAccess::update_meta_value(
    'alert_labels', $serialized_alert_labels
);

// Reload alerts page.
header('Location: ../index.php?view=alerts');