<?php
/**
 * Name: WAVE
 * Description: Links to WCAG 2.1 page reports.
 */

/**
 * WAVE Fields
 */
function wave_fields(){

    $wave_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Meta values.
                'meta' => [
                    array(
                        'name'     => 'wave_key',
                        'value'     => '',
                    )
                ]
            
        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'wave_key',
                    'label'    => 'WAVE Account Key',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $wave_fields;

}

 /**
  * WAVE URLs
  * Maps site URLs to Little Forest URLs for processing.
  */
function wave_urls($page_url) {
    return 'https://wave.webaim.org/api/request?key='.DataAccess::get_meta_value('wave_key').'&url='.$page_url.'&reporttype=4';
}

/**
 * Wave Alerts
 * @param string response_body
 * @param string page_url
 */
function wave_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $wave_alerts = [];
    $wave_json = $response_body; 

    // Decode JSON and count WCAG errors.
    $wave_json_decoded = json_decode($wave_json, true);

    // Fallback if WAVE scan doesn't work.
    if(!empty($wave_json_decoded['status']['error']))
        throw new Exception('WAVE error:"'.$wave_json_decoded['status']['error'].'"');

    // Sometimes WAVE can't read the json.
    if(empty($wave_json_decoded)){

        // We'll set the attributes to empty.
        $wave_errors = array();
        $wave_contrast_errors = array();
        $wave_warnings = array();

        // And add an alert.
        $alert = array(
            'source'  => 'wave',
            'url'     => $page_url,
            'type'    => 'error',
            'message' => 'WAVE cannot reach the page.',
            'guideline' => '',
            'tag'       => ''
        );
        array_push($wave_contrast_errors, $alert);

    }else{

        // Correctly working JSON gets the following attributes.
        $wave_errors = $wave_json_decoded['categories']['error'];
        $wave_contrast_errors = $wave_json_decoded['categories']['contrast'];
        $wave_warnings = $wave_json_decoded['categories']['alert'];
    
    }

    // Prevent a bug that occurs because LF adds "0" when no notices or errors.
    if($wave_errors == 0)
        $wave_errors = [];
    if($wave_warnings == 0)
        $wave_warnings = [];
    if($wave_contrast_errors == 0)
        $wave_contrast_errors = [];

    // Add alerts.
    $alert['source'] = 'wave';
    $alert['url'] = $page_url;
    $alert['guideline'] = '';
    $alert['tag'] = '';
    if(!empty($wave_errors) && ($wave_errors['count'] !== 0)) {
        $alert['message'] = $wave_errors['count'].' page errors found! See <a href="https://wave.webaim.org/report#/'.$page_url.'" target="_blank">WAVE report</a>.';
        $alert['type'] = 'error';
        $wave_alerts[] = $alert;
    }
    if(!empty($wave_warnings)) {
        $alert['message'] = $wave_warnings['count'].' page errors found! See <a href="https://wave.webaim.org/report#/'.$page_url.'" target="_blank">WAVE report</a>.';
        $alert['type'] = 'warning';
        $wave_alerts[] = $alert;

    }

    if(!empty($wave_contrast_errors)) {
        $alert['message'] = $wave_contrast_errors['count'].' contrast page errors found! See <a href="https://wave.webaim.org/report#/'.$page_url.'" target="_blank">WAVE report</a>.';
        $alert['type'] = 'error';
        $wave_alerts[] = $alert;
    }

    // Return alerts.
    return $wave_alerts;

}