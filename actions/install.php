<?php
// Info on DB must be declared to use db.php models.
require_once 'models/db.php';

// All the tables are created with this action.
if(DataAccess::table_exists('alerts') == false)
    DataAccess::create_alerts_table();
if(DataAccess::table_exists('pages') == false)
    DataAccess::create_pages_table();
if(DataAccess::table_exists('scans') == false)
    DataAccess::create_scans_table();
if(DataAccess::table_exists('meta') == false){
    DataAccess::create_meta_table();
    
// We need to add some meta to initialize the table
// since we don't use the INSERT statement anywhere.
DataAccess::add_meta('usage', 0);

}
