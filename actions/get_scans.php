<?php
// Require files to control db.
require '../config.php';
require '../models/db.php';
require '../models/view_components.php';

// Scan info is passed to JSON on the view, so that we can do 
// async scans.
$scans = DataAccess::get_scans();
the_scan_rows($scans);