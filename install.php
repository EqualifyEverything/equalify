<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's set up all the tables that Equalify needs to run!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// All the tables are created with this action.
if(DataAccess::table_exists('alerts') == false)
    DataAccess::create_alerts_table();
if(DataAccess::table_exists('queued_alerts') == false)
    DataAccess::create_queued_alerts_table();
if(DataAccess::table_exists('scan_profiles') == false)
    DataAccess::create_scan_profiles_table();
if(DataAccess::table_exists('tags') == false)
    DataAccess::create_tags_table();
if(DataAccess::table_exists('meta') == false)
    DataAccess::create_meta_table();