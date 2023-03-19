<?php
/**
 * Name: WAVE
 * Description: Automated testing, focused on issues that impact end users.
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
 * WAVE Tags
 */
function wave_tags(){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));
    
    // Read the JSON file - pulled from https://wave.webaim.org/api/docs?format=json
    $wave_tag_json = file_get_contents(__DIR__.'/wave_tags.json');
    $wave_tags = json_decode($wave_tag_json,true);

    // Convert WAVE format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if(!empty($wave_tags)){
        foreach($wave_tags as $wave_tag){

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">'.$wave_tag['summary'].'</p>';
            if(!empty($wave_tag['guidelines'])){
                $description.= '<p><strong>Guidelines:</strong> ';
                $copy = $wave_tag['guidelines'];
                foreach($wave_tag['guidelines'] as $guideline){
                    $description.= '<a href="'.$guideline['link'].'">'.$guideline['name'].'</a>';
                    if (next($copy ))
                        $description.= ', ';
                }
                $description.= '</p>';
            }
            
            // Now lets put it all together into the Equalify format.
            array_push(
                $tags, array(
                    'slug' => $wave_tag['name'],
                    'title' => $wave_tag['title'],
                    'category' => $wave_tag['category'],
                    'description' => $description
                )
            );

        }
    }

    // Return tags.
    return $tags;

}

 /**
  * WAVE request builder.
  * Maps site URLs to WAVE URLs for processing.
  */
function wave_single_page_request($page_url) {
    $request_url = '';
    $wave_key = DataAccess::get_meta_value('wave_key');

    // wave_key is required
    if (empty($wave_key)) {
        throw new Exception('WAVE key is not entered. Please add the WAVE key in the integration settings.');
    } else {
        $request_url = 'https://wave.webaim.org/api/request?key='.$wave_key.'&reporttype=4&url='.$page_url;
    }

    return [
        'method' => 'GET',
        'uri'  => $request_url,
    ];
}

/**
 * Wave Alerts
 * @param string response_body
 * @param string page_url
 */
function wave_single_page_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $wave_alerts = [];

    // Decode JSON.
    $wave_json_decoded = json_decode($response_body, true);
    
    // Fallback: Empty/unparsable response .
    if (empty($wave_json_decoded)) {
        $alert = array(
            'source'  => 'wave',
            'url'     => $page_url,
            'message' => 'Could not parse WAVE response.',
        );
        $wave_alerts[] = $alert;
        return $wave_alerts;
    // Fallback: WAVE API error response.
    } elseif (!empty($wave_json_decoded['status']['error'])) {
        $alert = array(
            'source'  => 'wave',
            'url'     => $page_url,
            'message' => 'WAVE error:"'.$wave_json_decoded['status']['error'].'"',
            'more_info' => json_encode($wave_json_decoded, JSON_PRETTY_PRINT),
        );
        $wave_alerts[] = $alert;
        return $wave_alerts;
    }

    // Process alerts.
    $errors = $wave_json_decoded['categories']['error']['items'] ?? [];
    $contrast_errors = $wave_json_decoded['categories']['contrast']['items'] ?? [];
    $alerts = $wave_json_decoded['categories']['alert']['items'] ?? [];
    $all_issues = array_merge($errors, $contrast_errors, $alerts);

    foreach ($all_issues as $issue) {
            // Build alert.
            $alert = [
                'source' => 'wave',
                'url' => $page_url,
                'tags' => 'wave_'.$issue['id'],
                'more_info' => json_encode($issue, JSON_PRETTY_PRINT),
            ];

            // Build message.
            if ($issue['count'] > 1) {
                $appended_text = ' (total: '.$issue['count'].')';
            } else {
                $appended_text = '';
            }
            $alert['message'] = $issue['description']
                . $appended_text
                . ' - <a href="https://wave.webaim.org/report#/'
                . $page_url
                . '" target="_blank">WAVE Report <span class="screen-reader-only">(opens in a new tab)</span></a>';

            $wave_alerts[] = $alert;
    }

    return $wave_alerts;

}