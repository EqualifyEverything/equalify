<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

// Get variables and set fallbacks.
$property_url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
if($property_url == false)
    throw new Exception('Format is invalid of the URL "'.$_GET['url'].'"');
$property_type = $_GET['type'];
if( $property_type == false)
    throw new Exception('Property type is not specified for the URL "'.$property_url.'"');

// Add backslash if no backslash exists 
// because WP API automatically gives backslashes to properties
// and we need the same url to set parent
if( !str_ends_with($property_url, '/') )
    $property_url = $property_url.'/';

// Check if URL is unique.
if(!is_unique_property_url($db, $property_url))
    throw new Exception('The URL "'.$property_url.'" is already added');

// Set URL Based on Type
if($property_type == 'wordpress'){
    $wp_property_url = $property_url.'/wp-json/wp/v2/pages?per_page=100';

    // Get URL contents and create WP properties.
    $url_contents = get_url_contents($wp_property_url);
    create_wordpress_properties($db, $property_url, $url_contents);

}else{
    throw new Exception('"'.$property_type.'" properties are not supported');
}

/**
 * Get URL Contents
 */
function get_url_contents($property_url){
    $curl = curl_init($property_url);
    curl_setopt($curl, CURLOPT_URL, $property_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');
    $url_contents = curl_exec($curl);
    return $url_contents;
    curl_close($curl);
    if($url_contents == false)
        throw new Exception('Contents of "'.$property_url.'" cannot be loaded');
}

/**
 * Create property records via WordPress API data.
 */
function create_wordpress_properties($db, $property_url, $url_contents){

    // Create JSON
    $wp_api_json = json_decode($url_contents, true);
    if(empty($wp_api_json[0]))
        throw new Exception('This content is not valid: <br>'.$url_contents.'</br>');

    // Push JSON to Properties Array
    $properties = [];
    foreach ($wp_api_json as $property):
        array_push($properties, array('url' => $property['link'], 'wcag_errors' => NULL));
    endforeach;

    // Set Account Info
    $account_info = get_account($db, USER_ID);

    // Check if user has credits 
    if($account_info->credits < count($properties))
        throw new Exception('Account "'.$account_info.'" does not have enough credits.');

    // SSL-Hack
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );

    // Conditional WAVE Accessibility Test
    if($account_info->accessibility_testing_service == 'WAVE'){

        // Loop Properties
        foreach ($properties as &$property):
            
            // Get WAVE data.
            $wave_url = 'https://wave.webaim.org/api/request?key='.$account_info->wave_key.'&url=sdf'.$property['url'];
            $wave_json = file_get_contents($wave_url, false, stream_context_create($override_https));
            $wave_json_decoded = json_decode($wave_json, true);        

            // Fallback if Wave ccan doesn't work.
            if($wave_json_decoded['status']['success'] == false)
                throw new Exception('WAVE scan related to page "'.$wave_url.'" failed with the error "'.$wave_json_decoded['status']['error'].'"');

            // Decode JSON and count WCAG errors.
            $wave_errors = $wave_json_decoded['categories']['error']['count'];

            // Update post meta.
            $property['wcag_errors'] = $wave_errors;
        
        endforeach;

    // Conditional Little Forrest Accessibility Test
    }elseif($account_info->accessibility_testing_service == 'Little Forrest'){

        // Loop $properties
        foreach ($properties as &$property):
            
            // Get Little Forrest data.
            $little_forrest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$property['url'].'&level=WCAG2AA';
            $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));

            // Fallback if LF scan doesn't work
            if(strpos($little_forrest_json, 'NoSuchFileException'))
                throw new Exception('Little Forrest error related to page "'.$little_forrest_url.'"');

            // Decode JSON and count WCAG errors.
            $little_forrest_json_decoded = json_decode($little_forrest_json, true);
            $little_forrest_errors = count($little_forrest_json_decoded['Errors']);
            if($little_forrest_errors == NULL)
                $little_forrest_errors = 0;

            // Update post meta.
            $property['wcag_errors'] = $little_forrest_errors;
        
        endforeach;

    // Fallback if no testing service is provided.
    }else{

        throw new Exception('No testing service is specified for account '.$account_info.'.');

    }

    // Create property records array to be used in SQL.
    $properties_records = [];

    // Insert Properties.
    foreach ($properties as &$property):


        // Set parent.
        if($property['url'] == $property_url || $property['url'] == $property_url.'/'){
            $property_parent = '';  
        }else{
            $property_parent = $property_url;                    
        }

        // Push each property to properties' records.
        // TODO: Make parent an id instead of URL
        array_push(
            $properties_records, 
            array(
                'url' => $property['url'], 
                'wcag_errors' => $property['wcag_errors'],
                'parent' => $property_parent
            )
        );


    endforeach;

    // Insert Properties
    insert_properties($db, $properties_records);

    // Subtract Used credit
    subtract_account_credits($db, USER_ID, count($properties) );

    // Redirect
    header("Location: ../index.php?view=properties&status=success");

}

/**
 * Create static property records.
 */
