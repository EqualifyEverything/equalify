<?php
// Require files to control db.
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/cron.php';

// Add scan to queue.
$time = date('Y-m-d H:i:s');
DataAccess::add_scan('queued', $time);

// Return to scan page without 'success' because
// queuing a scan is feedback enough.
header('Location: ../index.php?view=scans');