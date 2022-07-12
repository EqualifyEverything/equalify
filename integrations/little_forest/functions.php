<?php
/**
 * Name: Little Forest
 * Description: Counts WCAG 2.1 errors and links to page reports.
 */

/**
 * little_forest Scans
 */
function little_forest_scans($url){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/helpers/add_alert.php');

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
    $little_forest_errors = $little_forest_json_decoded['Errors'];
    $little_forest_notices = $little_forest_json_decoded['Notices'];
    $little_forest_warnings = $little_forest_json_decoded['Warnings'];
    if($little_forest_errors == NULL)
        $little_forest_errors = 0;

    // Prevent a bug that occurs because LF adds "0" when no notices or errors.
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

            // Add notice.
            $attributes = array(
                'source'  => 'Little Forrest',
                'url'     => $url,
                'type'    => 'error',
                'message' => $message,
                'meta'    => $meta
            );
            add_alert( $attributes );

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

            // Add notice.
            $attributes = array(
                'source'  => 'Little Forrest',
                'url'     => $url,
                'type'    => 'notice',
                'message' => $message,
                'meta'    => $meta
            );
            add_alert( $attributes );

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

            // Add warning.
            $attributes = array(
                'source'  => 'Little Forrest',
                'url'     => $url,
                'type'    => 'warning',
                'message' => $message,
                'meta'    => $meta
            );
            add_alert( $attributes );

        }
    }

}