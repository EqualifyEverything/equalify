<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
require_once '../models/adder.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get URL variabls and set fallbacks.
$url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($url == false)
    throw new Exception('URL"'.$_GET['url'].'" format is invalid');

// Add backslash if no backslash exists 
// because WP API automatically gives backslashes to properties
// and we need the same url to set group
if( !str_ends_with($url, '/') )
    $url = $url.'/';

// Get other variables after we set the URL variable correctly
$type = $_GET['type'];
if( $type == false)
    throw new Exception('Property type is not specified for the URL "'.$url.'"');
$group = $_GET['group'];
if(empty($group)){
    $group = $url;
    $is_parent = 1;
}else{
    $is_parent = '';
    if(filter_input(INPUT_GET, 'group', FILTER_VALIDATE_URL) == false)
        throw new Exception('Group URL"'.$_GET['group'].'" format is invalid');
}
if($type == 'wordpress' && $group != $url)
    throw new Exception('WordPress preproperties like "'.$url.'" cannot have groups');

// Only add unique properties that are static.
if(is_unique_property_url($db, $url) && $type == 'static' ){
    add_property($db, $url, $type, 'active', $group, $is_parent );

// WordPress properties are treated differently..
}elseif(is_unique_property_url($db, $url) && $type == 'wordpress'){
    $properties = get_wordpress_properties($url);
    add_properties($db, $properties);

}else{

    // Fallback
    throw new Exception('Property "'.$url.'" already exists');

}

// Redirect
header("Location: ../index.php?view=properties&status=success");