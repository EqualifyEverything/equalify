<?php
// Info on DB must be declared to use db.php models.
require_once 'models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// All the tables are created with this action.
if(table_exists($db, 'alerts') == false)
    create_alerts_table($db);
if(table_exists($db, 'meta') == false)
    create_meta_table($db);
if(table_exists($db, 'pages') == false)
    create_pages_table($db);
if(table_exists($db, 'scans') == false)
    create_scans_table($db);