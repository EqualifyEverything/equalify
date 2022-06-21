<?php
// Info on DB must be declared to use db.php models.
require_once 'models/db.php';

// All the tables are created with this action.
if(DataAccess::table_exists('alerts') == false)
    DataAccess::create_alerts_table();
if(DataAccess::table_exists('sites') == false)
    DataAccess::create_sites_table();
if(DataAccess::table_exists('scans') == false)
    DataAccess::create_scans_table();
if(DataAccess::table_exists('meta') == false){
    DataAccess::create_meta_table();

}
