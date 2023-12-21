<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's set up all the tables that Equalify needs to run!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
 **********************************************************/

// All the tables are created with this action.
if (DataAccess::table_exists('occurrences') == false)
    DataAccess::create_occurrences_table();
if (DataAccess::table_exists('queued_occurrences') == false)
    DataAccess::create_queued_occurrences_table();
if (DataAccess::table_exists('properties') == false)
    DataAccess::create_properties_table();
if (DataAccess::table_exists('pages') == false)
    DataAccess::create_pages_table();
if (DataAccess::table_exists('tags') == false)
    DataAccess::create_tags_table();
if (DataAccess::table_exists('tag_relationships') == false)
    DataAccess::create_tag_relationships_table();
if (DataAccess::table_exists('meta') == false)
    DataAccess::create_meta_table();
if (DataAccess::table_exists('reports') == false)
    DataAccess::create_reports_table();
if (DataAccess::table_exists('updates') == false)
    DataAccess::create_updates_table();
if (DataAccess::table_exists('queued_scans') == false)
    DataAccess::create_queued_sitemaps_table();
if (DataAccess::table_exists('queued_sitemaps') == false)
    DataAccess::create_queued_sitemaps_table();
