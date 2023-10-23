<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document saves a property to the DB.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';

// Validate required content.
$name = $_POST['name'];
if ($name == false)
    throw new Exception('Property name is missing.');
$url = $_POST['url'];
if ($url == false)
    throw new Exception('URL is missing.');
$status = $_POST['status'];
if ($status == false)
    throw new Exception(
        'Status is missing.'
    );
$crawl_type = $_POST['crawl_type'];
if (!in_array($crawl_type, ['single_page', 'xml', 'false']))
    throw new Exception(
        'Type is missing or incorrect.'
    );
$frequency = $_POST['frequency'];
$possible_frequency = ["manually","hourly", "daily", "weekly", "monthly", "false"];
    if ( !in_array($frequency, $possible_frequency))
        throw new Exception(
            'Frequency is missing or incorrect.'
        );

// Let's setup the tests array, which is not required.
$tests = [];
if (isset($_POST['automated_scan'])) {
  $tests[] = 'automated_scan';
}
if (isset($_POST['ai_scan'])) {
  $tests[] = 'ai_scan';
}

// If no errors occur, we can add these profiles into the URL
// with several default items.
$fields = array(
    array(
        'name' => 'name',
        'value' => $name
    ),
    array(
        'name' => 'url',
        'value' => $url
    ),
    array(
        'name' => 'status',
        'value' => $status
    ),
    array(
        'name' => 'crawl_type',
        'value' => $crawl_type
    ),
    array(
        'name' => 'frequency',
        'value' => $frequency
    ),
    array(
        'name' => 'tests',
        'value' => serialize($tests)
    ),

);

// Existing properties are declared via the
// session variable.
session_start();
$proper =$_SESSION['property_id'];
if(!empty($_SESSION['property_id'])){
    DataAccess::update_db_entry('properties', $_SESSION['property_id'], $fields);
}else{

    // New properties are saved as a new DB entry.
    DataAccess::add_db_entry(
        'properties', $fields
    );
    
}

// Back home we go.
header('Location: ../index.php?view=settings');