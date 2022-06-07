<?php
// Require files to control db.
include '../config.php';
include '../models/db.php';
include '../models/cron.php';
include '../models/view_components.php';

// Add scan to cue.
$time = date('Y-m-d H:i:s');
DataAccess::add_scan('cued', $time);

// Return to scan page without 'success' because
// cueing a scan is feedback enough.
header('Location: ../index.php?view=scans');