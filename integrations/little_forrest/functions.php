<?php
/**
 * Name: Little Forrest
 * Description: Counts WCAG 2.1 errors and links to page reports.
 */

/**
 * Little Forrest Fields
 */
function little_forrest_fields(){

    $little_forrest_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Pages fields.
                'pages' => [
                    array(
                        'name' => 'little_forrest_wcag_2_1_errors',
                        'type'  => 'VARCHAR(20)'
                    )
                ]
            
        ]
        
    );

    // Return fields
    return $little_forrest_fields;

}

/**
 * little_forrest Scans
 */
function little_forrest_scans($page){

    // Add DB info and required functions.
    require_once '../config.php';
    require_once '../models/db.php';

    // Get Little Forrest data.
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );
    $little_forrest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$page->url.'&level=WCAG2AA&cache=false';
    $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));

    // Fallback if LF scan doesn't work.
    if(strpos($little_forrest_json, 'NoSuchFileException'))
        throw new Exception('Little Forrest error related to page "'.$little_forrest_url.'"');

    // Decode JSON and count WCAG errors.
    $little_forrest_json_decoded = json_decode($little_forrest_json, true);
    $little_forrest_errors = $little_forrest_json_decoded['Errors'];
    $little_forrest_notices = $little_forrest_json_decoded['Notices'];
    $little_forrest_warnings = $little_forrest_json_decoded['Warnings'];
    if($little_forrest_errors == NULL)
        $little_forrest_errors = 0;
        
    // Remove previously saved alerts.
    $alerts_filter = [
        array(
            'name'   =>  'page_id',
            'value'  =>  $page->id
        ),
        array(
            'name'   =>  'page_id',
            'value'  =>  $page->id
        ),
        array(
            'name'   =>  'integration_uri',
            'value'  =>  'little_forrest'
        )
    ];
    DataAccess::delete_alerts($alerts_filter);

    // Prevent a bug that occurs because LF adds "0" when no notices or errors.
    if($little_forrest_errors == 0)
        $little_forrest_errors = [];
    if($little_forrest_warnings == 0)
        $little_forrest_warnings = [];

    // Add errors.
    if(count($little_forrest_errors) >= 1){
        foreach($little_forrest_errors as $error){

            // Create Meta
            $meta = array(
                'guideline' => $error['Guideline'],
                'tag'       =>  $error['Tag']
            );

            // Create message.
            if(!empty($error['Code'])){
                $code = htmlentities('[code]'.$error['Code'].'[/code]');
                $message = $code.$error['Message'];
            }else{
                $message = $error['Message'];
            }

            // Add notice.
            DataAccess::add_page_alert($page->id, $page->site,'little_forrest', 'error', $message, $meta);

        }
    }

    // Add notices.
    if(count($little_forrest_notices) >= 1){
        foreach($little_forrest_notices as $notice){

            // Create Meta
            $meta = array(
                'guideline' => $notice['Guideline'],
                'tag'       => $notice['Tag']
            );

            // Create message.
            if(!empty($error['Code'])){
                $code = htmlentities('[code]'.$error['Code'].'[/code]');
                $message = $code.$error['Message'];
            }else{
                $message = $error['Message'];
            }

            // Add notice.
            DataAccess::add_page_alert($page->id, $page->site,'little_forrest', 'notice', $message, $meta);

        }
    }

    // Add warnings.
    if(count($little_forrest_warnings) >= 1){
        foreach($little_forrest_warnings as $warning){

            // Create Meta
            $meta = array(
                'guideline' => $warning['Guideline'],
                'tag'       => $warning['Tag']
            );

            // Create message.
            if(!empty($error['Code'])){
                $code = htmlentities('[code]'.$error['Code'].'[/code]');
                $message = $code.$error['Message'];
            }else{
                $message = $error['Message'];
            }

            // Add warning.
            DataAccess::add_page_alert($page->id, $page->site,'little_forrest', 'warning', $message, $meta);

        }
    }


    // Update page data.
    DataAccess::update_page_data($page->id, 'little_forrest_wcag_2_1_errors', count($little_forrest_errors));

}