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
if (!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// We'll use the directory to include required files.
require_once(__ROOT__ . '/config.php');
require_once(__ROOT__ . '/models/db.php');

// Random text helper.
function generate_random_text($length = 20)
{
    $words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
        'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
        'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua'
    ];

    $generateSentence = function ($length) use ($words) {
        shuffle($words);
        return ucfirst(implode(' ', array_slice($words, 0, $length)));
    };

    $random_text = $generateSentence($length);

    return  $random_text;
}

// For generating random time stamps
function generate_random_timestamp()
{
    $start = strtotime('2023-01-01');
    $end = strtotime('2023-12-01');
    $random_timestamp = mt_rand($start, $end);

    return date('Y-m-d H:i:s', $random_timestamp);
}

// Generate properties.
generate_properties();
function generate_properties()
{
    $properties = array(
        array(
            'url' => 'http://pih.org/sitemap.xml',
            'name' => 'PIH Pages',
            'crawl_type' => 'xml',
            'frequency' => 'manually',
            'status' => 'archived',
            'tests' => ''
        ),
        array(
            'url' => 'https://equalify.app',
            'name' => 'Equalify Homepage',
            'crawl_type' => 'single_page',
            'frequency' => 'hourly',
            'status' => 'active',
            'tests' => ''
        ),
        array(
            'url' => 'https://newURL.app',
            'name' => 'New URL Test Homepage',
            'crawl_type' => 'single_page',
            'frequency' => 'manually',
            'status' => 'archived',
            'tests' => ''
        ),
        array(
            'url' => 'https://decubing.com',
            'name' => 'Decubing Site',
            'crawl_type' => 'wordpress',
            'frequency' => 'hourly',
            'status' => 'active',
            'tests' => ''
        )
    );

    // Get all properties (not just active ones) to avoid duplicates.
    $properties_filter = array();  // empty filter to get all rows.
    $existing_properties = DataAccess::get_db_rows(
        'properties',
        $properties_filter,
        1,
        10000
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
        }
        $count++;

        $fields = array(
            array('name' => 'url', 'value' => $property['url']),
            array('name' => 'name', 'value' => $property['name']),
            array('name' => 'crawl_type', 'value' => $property['crawl_type']),
            array('name' => 'frequency', 'value' => $property['frequency']),
            array('name' => 'status', 'value' => $property['status']),
            array('name' => 'tests', 'value' => $property['tests'])
        );

        DataAccess::add_db_entry('properties', $fields);
    }

    echo "Added " . $count . " properties. \n";
}

// Generate notices.
generate_notices();
function generate_notices()
{

    // Get all properties
    $properties_filter = array();
    $existing_properties = DataAccess::get_db_rows('properties', $properties_filter, 1, 10000)['content'];
    // Get all tags
    $tags_filter = array();
    $existing_tags = DataAccess::get_db_rows(
        'tags',
        $tags_filter,
        1,
        10000
    )['content'];

    $statuses = ["active", "ignored", "equalified"];

    $new_notices = [];
    for ($i = 0; $i < 100; $i++) {
        $random_tags = [];
        $random_tag_indexes = array_rand($existing_tags, 3);
        foreach ($random_tag_indexes as $index) {
            $random_tags[] = $existing_tags[$index]->title;
        }
        $random_property = $existing_properties[array_rand($existing_properties)];
        $notice = new stdClass();
        $notice->related_url = $random_property->url;
        $notice->property_id = $random_property->id;
        $notice->source = 'faked_data.php';
        $notice->status = $statuses[array_rand($statuses)];
        $notice->tags =  $random_tags;
        $notice->message = generate_random_text(3) . '.';
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

// Generate reports.
generate_reports();
function generate_reports()
{

    $new_reports = [];
    for ($i = 0; $i < 11; $i++) {

        $report = new stdClass();
        $report->title = generate_random_text(3);

        $new_reports[] = $report;
    }

    // Add reports to database
    $rows = [];
    foreach ($new_reports as $report) {
        $new_row = array(
            'title' => $report->title
        );
        array_push($rows, $new_row);
    }

    DataAccess::add_db_rows('reports', $rows);

    // Add message.
    $count_reports = count($new_reports);
    echo "Added $count_reports reports. \n";
}

generate_logs();
function generate_logs()
{
    $statuses = ["active", "ignored", "equalified"];
    $new_logs = [];
    for ($i = 0; $i < 11; $i++) {

        $new_log = new stdClass();
        $new_log->time = generate_random_timestamp();
        $new_log->notice_id = rand(1, 100);
        $new_log->action = $statuses[rand(0, 2)];
        $new_logs[] =  $new_log;
    }
    $rows = [];
    foreach ($new_logs as $log) {
        $new_row = array(
            'time' => $log->time,
            'notice_id' => $log->notice_id,
            'action' => $log->action
        );
        array_push($rows, $new_row);
    }
    DataAccess::add_db_rows('logs', $rows);

    $count_logs = count($new_logs);
    echo "Added $count_logs logs. \n";
}
