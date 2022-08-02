<?php
/**
 * Name: Little Forest
 * Description: Counts WCAG 2.1 errors and links to page reports.
 */

/**
 * Little Forest Alerts
 * @param string url
 */
function little_forest_alerts($url){

    // Our goal is to return alerts.
    $little_forest_alerts = array();

    // Get Little Forest data.
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );
    $little_forest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$url.'&level=WCAG2AA&cache=false';
    $little_forest_json = file_get_contents($little_forest_url, false, stream_context_create($override_https));

    // Fallback if LF scan doesn't work.
    if(strpos($little_forest_json, 'NoSuchFileException'))
        throw new Exception('Little Forest error related to page "'.$little_forest_url.'"');

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
            'url'     => $url,
            'type'    => 'error',
            'message' => 'Little Forest cannot reach the page.',
            'meta'    => ''
        );
        array_push($little_forest_alerts, $alert);


    }else{

        // Correctly working JSON gets the following attributes.
        $little_forest_errors = $little_forest_json_decoded['Errors'];
        $little_forest_notices = $little_forest_json_decoded['Notices'];
        $little_forest_warnings = $little_forest_json_decoded['Warnings'];
    
    }

    // Prevent a bug that occurs because LF adds 
    // "0" when no notices or errors.
    if($little_forest_errors == 0)
        $little_forest_errors = [];
    if($little_forest_warnings == 0)
        $little_forest_warnings = [];

    // Add errors.
    if(count($little_forest_errors) >= 1){
        foreach($little_forest_errors as $error){

            // Create Meta
            $meta = array(
                'guideline' => $error['Guideline'],
                'tag'       =>  $error['Tag']
            );

            // Create message.
            if(!empty($error['Code']) && $error['Code'] != 'null'){
                $code = htmlentities('[code]'.$error['Code'].'[/code]');
                $message = $code.$error['Message'];
            }else{
                $message = $error['Message'];
            }

            // Push alert to returnable array.
            $alert = array(
                'source'  => 'little_forest',
                'url'     => $url,
                'type'    => 'error',
                'message' => $message,
                'meta'    => $meta
            );
            array_push($little_forest_alerts, $alert);

        }
    }

    // Add notices.
    if(count($little_forest_notices) >= 1){
        foreach($little_forest_notices as $notice){

            // Create Meta
            $meta = array(
                'guideline' => $notice['Guideline'],
                'tag'       => $notice['Tag']
            );

            // Create message.
            if(!empty($notice['Code']) && $notice['Code'] != 'null'){
                $code = htmlentities('[code]'.$notice['Code'].'[/code]');
                $message = $code.$notice['Message'];
            }else{
                $message = $notice['Message'];
            }

            // Push alert to returnable array.
            $alert = array(
                'source'  => 'little_forest',
                'url'     => $url,
                'type'    => 'notice',
                'message' => $message,
                'meta'    => $meta
            );
            array_push($little_forest_alerts, $alert);

        }
    }

    // Add warnings.
    if(count($little_forest_warnings) >= 1){
        foreach($little_forest_warnings as $warning){

            // Create Meta
            $meta = array(
                'guideline' => $warning['Guideline'],
                'tag'       => $warning['Tag']
            );

            // Create message.
            if(!empty($warning['Code']) && $warning['Code'] != 'null'){
                $code = htmlentities('[code]'.$warning['Code'].'[/code]');
                $message = $code.$warning['Message'];
            }else{
                $message = $warning['Message'];
            }

            // Push alert to returnable array.
            $alert = array(
                'source'  => 'little_forest',
                'url'     => $url,
                'type'    => 'warning',
                'message' => $message,
                'meta'    => $meta
            );
            array_push($little_forest_alerts, $alert);

        }
    }

    // Return alerts.
    return $little_forest_alerts;

}