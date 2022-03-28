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

// Get variables and set fallbacks.
$url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($url == false)
    throw new Exception('URL"'.$_GET['url'].'" format is invalid');
$type = $_GET['type'];
if( $type == false)
    throw new Exception('Property type is not specified for the URL "'.$url.'"');
$parent = $_GET['parent'];
if(empty($parent)){
    $parent = '';
}else{
    if(filter_input(INPUT_GET, 'parent', FILTER_VALIDATE_URL) == false)
        throw new Exception('Parent URL"'.$_GET['parent'].'" format is invalid');
}
if($type == 'wordpress' && $parent != '')
    throw new Exception('WordPress preproperties like "'.$url.'" cannot have parents');

// Add backslash if no backslash exists 
// because WP API automatically gives backslashes to properties
// and we need the same url to set parent
if( !str_ends_with($url, '/') )
    $url = $url.'/';

// Add Unique Property
if(is_unique_property_url($db, $url) && $type == 'static' ){

    // Static Property
    add_property($db, $url, $type, 'active', $parent );

}elseif(is_unique_property_url($db, $url) && $type == 'wordpress'){

    // WordPress Properties
    $properties = get_wordpress_properties($url);
    add_properties($db, $properties);

}else{

    // Fallback
    throw new Exception('Property "'.$url.'" already exists');

}

// Redirect
header("Location: ../index.php?view=properties&status=success");