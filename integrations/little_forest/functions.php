<?php
/**
 * Name: Little Forest
 * Description: Reports WCAG 2.1 page alerts.
 */

 /**
  * Maps site URLs to Little Forest URLs for processing.
  */
function little_forest_urls($page_url) {
    return 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$page_url.'&level=WCAG2AA&cache=false';
}

/**
 * Little Forest Alerts
 * @param string url
 */
function little_forest_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $little_forest_alerts = [];
    $little_forest_json = $response_body; 

    // Fallback if LF scan doesn't work.
    if(strpos($little_forest_json, 'NoSuchFileException'))
        throw new Exception('Little Forest error related to page "'.$page_url.'"');

    // Decode JSON and count WCAG errors.
    $little_forest_json_decoded = json_decode($little_forest_json, true);

    // Sometimes LF can't read the json.
    if(empty($little_forest_json_decoded)){

        // We'll set the attributes to empty.
        $little_forest_errors = array();
        $little_forest_notices = array();
        $little_forest_warnings = array();

        // And add an alert.
        $alert = array(
            'source'  => 'little_forest',
            'url'     => $page_url,
            'type'    => 'error',
            'message' => 'Little Forest cannot reach the page.',
            'guideline' => '',
            'tag'       => ''
        );
        array_push($little_forest_alerts, $alert);


    }else{

        // Correctly working JSON gets the following attributes.
        $little_forest_errors = $little_forest_json_decoded['Errors'];
        $little_forest_notices = $little_forest_json_decoded['Notices'];
        $little_forest_warnings = $little_forest_json_decoded['Warnings'];
    
    }

    // Prevent a bug that occurs because LF adds "0" when no notices or errors.
    if($little_forest_errors == 0)
        $little_forest_errors = [];
    if($little_forest_warnings == 0)
        $little_forest_warnings = [];
    if($little_forest_notices == 0)
        $little_forest_notices = [];

    // Add errors, warnings, and notices
    foreach ($little_forest_errors as $error) {
        $alert = build_alert($error, 'error', $page_url);
        $little_forest_alerts[] = $alert;
    }

    foreach ($little_forest_warnings as $warning) {
        $alert = build_alert($warning, 'warning', $page_url);
        $little_forest_alerts[] = $alert;
    }

    foreach ($little_forest_notices as $notice) {
        $alert = build_alert($notice, 'notice', $page_url);
        $little_forest_alerts[] = $alert;
    }

    // Return alerts.
    return $little_forest_alerts;

}

function build_alert($alert, string $type, string $url) {

    // Prepare guideline.
    $guideline = '';
    if(!empty($alert['Guideline'])){
        $pattern = "/(?:[^.]+\.){3}([^.]+).*/";
        $search = preg_match(
            $pattern, $alert['Guideline'], $guideline
        );
        if(!empty($search))
            $guideline = str_replace('_', '.', $guideline[1]);
    }

    // Setup tag.
    $tag = '';
    if(!empty($alert['Tag']) && $alert['Tag'] !== 'null')
        $tag = $alert['Tag'];

    // Create message.
    if(!empty($alert['Code']) && $alert['Code'] != 'null'){
        $code = htmlentities('[code]'.$alert['Code'].'[/code]');
        $message = $code.$alert['Message'];
    }else{
        $message = $alert['Message'];
    }

    // Push alert to returnable array.
    $alert = array(
        'source'  => 'little_forest',
        'url'     => $url,
        'type'    => $type,
        'message' => $message,
        'guideline' => $guideline,
        'tag'       => $tag
    );

    return $alert;
}