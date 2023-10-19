<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document generates sample data. You can use it
 * by calling this document in your CLI.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__.'/config.php');
require_once(__ROOT__.'/models/db.php');

// Generate properties.
generate_properties();
function generate_properties() {
    $properties = array(
        array(
            'url' => 'http://pih.org/sitemap.xml',
            'name' => 'PIH Pages',
            'crawl_type' => 'xml',
            'frequency' => 'manually',
            'status' => 'archived',
            'automated_tests' => ''
        ),
        array(
            'url' => 'https://equalify.app',
            'name' => 'Equalify Homepage',
            'crawl_type' => 'single_page',
            'frequency' => 'hourly',
            'status' => 'active',
            'automated_tests' => ''
        ),
        array(
            'url' => 'https://decubing.com',
            'name' => 'Decubing Site',
            'crawl_type' => 'wordpress',
            'frequency' => 'hourly',
            'status' => 'active',
            'automated_tests' => ''
        )
    );

    // Get all properties (not just active ones) to avoid duplicates.
    $properties_filter = array();  // empty filter to get all rows.
    $existing_properties = DataAccess::get_db_rows(
        'properties', $properties_filter, 1, 10000
    )['content'];

    $existing_urls = array();
    foreach ($existing_properties as $property) {
        $existing_urls[] = $property->url;
    }

    $count = 0;
    foreach ($properties as $property) {
        
        // Check if the property URL already exists
        if (in_array($property['url'], $existing_urls)) {
            continue;  // skip if URL already exists in the database
            $count++;
        }

        $fields = array(
            array('name' => 'url', 'value' => $property['url']),
            array('name' => 'name', 'value' => $property['name']),
            array('name' => 'crawl_type', 'value' => $property['crawl_type']),
            array('name' => 'frequency', 'value' => $property['frequency']),
            array('name' => 'status', 'value' => $property['status']),
            array('name' => 'automated_tests', 'value' => $property['automated_tests'])
        );

        DataAccess::add_db_entry('properties', $fields);
    }

    echo "Added ".$count." properties. \n";

}

/// Generate notices.
generate_notices();
function generate_notices(){

    // Get all properties
    $properties_filter = array();
    $existing_properties = DataAccess::get_db_rows('properties', $properties_filter, 1, 10000)['content'];

    $statuses = ["active", "ignored", "equalified"];
    $messages = [
        "lorem sic ipsum dalor",
        "sic lorem dalor ipsum",
        "ipsum dalor lorem sic",
        "dalor sic ipsum lorem",
        "lorem ipsum sic dalor"
    ];

    $new_notices = [];
    for ($i = 0; $i < 11; $i++) {
        $random_property = $existing_properties[array_rand($existing_properties)];

        $notice = new stdClass();
        $notice->related_url = $random_property->url;
        $notice->property_id = $random_property->id;
        $notice->source = 'faked_data.php';
        $notice->status = $statuses[array_rand($statuses)];
        $notice->tags = 'faked_data';
        $notice->message = $messages[array_rand($messages)];
        $notice->meta = '';  // Leaving meta blank
        $notice->archived = 0;

        $new_notices[] = $notice;
    }

    // Add notices to database
    $rows = [];
    foreach ($new_notices as $notice) {
        $new_row = array(
            'related_url' => $notice->related_url,
            'property_id' => $notice->property_id,
            'source' => $notice->source,
            'status' => $notice->status,
            'tags' => $notice->tags,
            'message' => $notice->message,
            'meta' => $notice->meta,
            'archived' => $notice->archived
        );
        array_push($rows, $new_row);
    }

    DataAccess::add_db_rows('notices', $rows);

    // Add message.
    $count_notices = count($new_notices);
    echo "Added $count_notices notices. \n";

}