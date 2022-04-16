<?php
/**
 * Name: Little Forrest
 * Description: Counts WCAG 2.1 errors and links to page reports.
 * Status: Disabled
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
function little_forrest_scans($page, $meta){

    // Add DB info and required functions.
    require_once '../config.php';
    require_once '../models/db.php';
    $db = connect(
        DB_HOST, 
        DB_USERNAME,
        DB_PASSWORD,
        DB_NAME
    );

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
    $little_forrest_errors = count($little_forrest_json_decoded['Errors']);
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
    delete_alerts($db, $alerts_filter);

    // Set optional alerts.
    if($little_forrest_errors > 1)
        add_page_alert($db, $page->id, $page->site,'little_forrest', 'WCAG 2.1 page errors found! See <a href="https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.$page->url.'&lang=auto&cache=false" target="_blank">Little Forrest report</a>.');

    // Update page data.
    update_page_data($db, $page->id, 'little_forrest_wcag_2_1_errors', $little_forrest_errors);
        
}