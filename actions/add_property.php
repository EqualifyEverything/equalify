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

// Property types must be specified 'cuz different types require different scans.
$type = $_GET['type'];
if( $type == false)
    die('Property type is not specified for the URL "'.$url.'".');

// Properties must have unique URLS.
if(!is_unique_property_url($db, $url))
    die('Property "'.$url.'" already exists.');

// Static pages are added individually.
if($type == 'single_page' ){
    add_property($db, $url, $type, 'active', $group = $url, $is_parent = 1 );

// WordPress and XML deal with adding properties and setting
// groups + status similarly, so they are in one condition.
}elseif($type == 'wordpress' || $type == 'xml' ){

    // WordPress API is queried to turn pages into properties.
    if($type == 'wordpress' ){

        // Lots of users don't include backslashes,
        // which WordPress required to access the API
        if( !str_ends_with($url, '/') )
            $url = $url.'/';

        // WordPress adder can create lots of properties
        $properties = wordpress_properties_adder($url);

    }

    // .XML adder can create lots of properties.
    if($type == 'xml' )
        $properties = xml_site_adder($url);

    // We're setting the status and adding properties here so we
    // do not have to call the $db inside "models/adders.php",
    // keeping each model focused on distinct functions.
    $properties_records = [];
    foreach ($properties as &$property):

        // Though these variables were set, we must reset them for
        // one property, since we have now generated many properties.
        if($group == $property['url']){
            $is_parent = 1;

            // This will be used later..
            $has_parent = true;

        }else{
            $is_parent = '';
        }

        // Push each property to properties' records.
        array_push(
            $properties_records, 
            array(
                'url'       => $property['url'], 
                'group'     => $url,
                'is_parent' => $is_parent,
                'status'    => $status,
                'type'      => $type
            )
        );

    endforeach; 

    // Some newly created record arrays do not have existing groups
    // and do not contain a parent because API/XML records contain 
    // different URLs than the URL where the API/XML exists. In that 
    // case, the first record, which is often the homepage, becomes 
    // parent and the URL the person entered becomes the group
    if(!isset($has_parent) && !isset($existing_group)){
        $first_record = $properties_records[0];
        $first_record['is_parent'] = 1;
        $group = $url;
        foreach ($properties_records as &$property){
            $property['group'] = $group;
        }
    }

    // Finalllly, we can add properties to the DB.
    add_properties($db, $properties_records);

// Since we're passing type through a URL, we have a fallback
// in case someone passes an unsupported 'type'. 
}else{
    die('"'.$type.'" properties are unsupported.');
}

// For better UX, we're redirecting to the details page with
// all the shiney new properties.
if(isset($property_records)){
    $redirect_uid =  get_property_details_uri($db, get_property_id($db, $property_records[0]->group));
}else{
    $redirect_uid = get_property_id($db, $url);
}
header('Location: ../index.php?view=property_details&id='.$redirect_uid.'&status=success');