<?php
// Require files to control db.
include '../config.php';
include '../models/db.php';
include '../models/cron.php';
include '../models/view_components.php';


// Add scan to cue.
$time = date('Y-m-d H:i:s');
$new_scan = DataAccess::add_scan('cued', $time);

// Create a new cron event with the time
// that's returned for the latest scan.
add_cron_event($time, 'php actions/run_scan.php');