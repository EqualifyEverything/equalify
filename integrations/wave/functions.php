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

        // And add an alert.
        $alert = array(
            'source'  => 'wave',
            'url'     => $page_url,
            'message' => 'WAVE cannot reach the page.',
        );
        array_push($wave_alerts, $alert);

    }else{

        // Reformat correctly working items.
        $wave_items = array();
        foreach($wave_json_decoded['categories'] as $wave_json_entry){

            // Only show alerts and errors.
            if(
                !empty($wave_json_entry['items'])
                && (
                    $wave_json_entry['description'] == 'Errors'
                    || $wave_json_entry['description'] == 'Contrast Errors'
                )
            ){
                foreach($wave_json_entry['items'] as $wave_item)
                    $wave_items[] = $wave_item;
            }

        }

    
    }

    // Add alerts.
    if(!empty($wave_items)) {

        // Setup alert variables.
        foreach($wave_items as $wave_item){

            // Default variables.
            $alert = array();
            $alert['source'] = 'wave';
            $alert['url'] = $page_url;

            // Setup tags.
            $alert['tags'] = $wave_item['id'];

            // Setup message.
            if($wave_item['count'] > 1){
                $appended_text = ' (total: '.$wave_item['count'].')';
            }else{
                $appended_text = '';
            }
            $alert['message'] = $wave_item['description'].$appended_text.' - <a href="https://wave.webaim.org/report#/'.$page_url.'" target="_blank">WAVE Report</a>';

            // Push alert.
            $wave_alerts[] = $alert;
            
        }

    }

    // Return alerts.
    return $wave_alerts;

}