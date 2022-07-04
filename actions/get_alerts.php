<?php
// Require files to control db.
require '../config.php';
require '../models/db.php';

// This changes the little red number asyncronistically with JS
// embedded in the view file.
echo DataAccess::count_db_rows('alerts');