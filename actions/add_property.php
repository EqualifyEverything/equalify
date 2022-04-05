<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/adders.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Valid URLs are required for each property.
$url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($url == false)
    die('URL"'.$_GET['url'].'" format is invalid or missing.');

// Add backslash if no backslash exists 
// because WP API automatically gives backslashes to properties
// and we need the same url to set group.
if( !str_ends_with($url, '/') )
    $url = $url.'/';

// Property types must be specified because different types require different scans.
$type = $_GET['type'];
if( $type == false)
    die('Property type is not specified for the URL "'.$url.'".');

// When group isn't included, property creates its own group..
if(empty($_GET['group'])){
    $group = $url;
    $is_parent = 1;

    // New groups have the active status by default.
    $status = 'active';

// ..otherwise, property inherits the select group attributes.
}else{
    $is_parent = '';
    $group = filter_input(INPUT_GET, 'group', FILTER_VALIDATE_URL);
    $status = get_group_parent_status($db, $group);
}

// Properties must have unique URLS.
if(!is_unique_property_url($db, $url))
    die('Property "'.$url.'" already exists.');

// Static pages are added individually.
if($type == 'static' ){
    add_property($db, $url, $type, 'active', $group, $is_parent );

// WordPress and Drupal deal with adding properties and setting
// groups + status similarly, so they are in one condition.
}elseif($type == 'wordpress' || $type == 'drupal' ){

    // WordPress uses an API to turn pages into properties.
    if($type == 'wordpress' )
        $properties = wordpress_properties_adder($url);

    // Drupal uses its XML sitemap to turn pages into properties.
    if($type == 'drupal' )
        $properties = drupal_properties_adder($url);

    // We're setting the status and adding properties so we don't
    // have to call the $db outside "models/adder.php".
    $properties_records = [];
    foreach ($properties as &$property):

        // New groups with many properties need to set one parent,
        // which is the main URL that initiated the adder.
        if($group == $property['url']){
            $is_parent = 1;
        }else{
            $is_parent = '';
        }

        // Push each property to properties' records.
        array_push(
            $properties_records, 
            array(
                'url'       => $property['url'], 
                'group'     => $group,
                'is_parent' => $is_parent,
                'status'    => $status,
                'type'      => $type
            )
        );

    endforeach; 
    add_properties($db, $properties_records);

// Since we're passing type through a URL, we a fallback in
// case someone passes an unsupported type. 
}else{
    throw new Exception('Property "'.$url.'" already exists');
}

// When the work is done, we can triumphantly return home.
header("Location: ../index.php?view=properties&status=success");