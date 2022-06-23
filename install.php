<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's setup all the tables that Equalify needs to run!
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// All the tables are created with this action.
if(DataAccess::table_exists('alerts') == false)
    DataAccess::create_alerts_table();
if(DataAccess::table_exists('sites') == false)
    DataAccess::create_sites_table();
if(DataAccess::table_exists('meta') == false)
    DataAccess::create_meta_table();
