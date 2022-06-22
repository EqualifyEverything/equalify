<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document adds a scan to our queue, which script.js
 * will process.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

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