<?php
/**
 * Name: axe-core
 * Description: An automated accessibility scan.
 */

/**
 * axe-core Fields
 */
function axe_fields(){

    $axe_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Meta values.
                'meta' => [
                    array(
                        'name'     => 'axe_uri',
                        'value'     => '',
                    )
                ]
            
        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'axe_uri',
                    'label'    => 'axe-core URI (ie- https://axe.equalify.app/?url=)',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $axe_fields;

}

/**
 * axe Tags
 */
function axe_tags(){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));
    
    // Read the JSON file - pulled from https://axe.webaim.org/api/docs?format=json
    $axe_tag_json = file_get_contents(__DIR__.'/axe_tags.json');
    $axe_tags = json_decode($axe_tag_json,true);

    // Convert axe format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if(!empty($axe_tags)){
        foreach($axe_tags as $axe_tag){

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">'.$axe_tag['description'].'</p>';
            
            // Now lets put it all together into the Equalify format.
            array_push(
                $tags, array(
                    'title' => $axe_tag['title'],
                    'category' => $axe_tag['category'],
                    'description' => $description,

                    // axe-core uses periods, which get screwed up
                    // when equalify serializes them, so we're
                    // just not going to use periods
                    'slug' => str_replace('.', '', $axe_tag['slug'])

                )
            );

        }
    }

    // Return tags.
    return $tags;

}

 /**
  * Axe URLs
  * Maps site URLs to Axe URLs for processing.
  */
function axe_urls($page_url) {

    // Require axe_uri
    $axe_uri = DataAccess::get_meta_value('axe_uri');
    if(empty($axe_uri)){
        throw new Exception('axe-core URI is not entered. Please add the URI in the integration settings.');
    }else{
        return $axe_uri.$page_url;
    }

}

/**
 * Axe Alerts
 * @param string response_body
 * @param string page_url
 */
function axe_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $axe_alerts = [];
    $axe_json = $response_body; 

    // Decode JSON.
    $axe_json_decoded = json_decode($axe_json);

    // Sometimes Axe can't read the json.
    if(!empty($axe_json_decoded)){

        // We add violations to this array.
        $axe_violations = array();

        // Show axe violations
        foreach($axe_json_decoded[0]->violations as $violation){

            // Only show violations.
            $axe_violations[] = $violation;

        }

        // Add alerts.
        if(!empty($axe_violations)) {

            // Setup alert variables.
            foreach($axe_violations as $violation){

                // Default variables.
                $alert = array();
                $alert['source'] = 'axe';
                $alert['url'] = $page_url;

                // Setup tags.
                $alert['tags'] = '';
                if(!empty($violation->tags)){

                    // We need to get rid of periods so Equalify
                    // wont convert them to underscores and they
                    // need to be comma separated.
                    $tags = $violation->tags;
                    $copy = $tags;
                    foreach($tags as $tag){
                        $alert['tags'].= str_replace('.', '', 'axe_'.$tag);
                        if (next($copy ))
                            $alert['tags'].= ',';
                    }
                }                

                // Setup message.
                $alert['message'] = '"'.$violation->id.'" violation: '.$violation->help;

                // Setup more info.
                $alert['more_info'] = '';
                if($violation->nodes)
                    $alert['more_info'] = $violation->nodes;

                // Push alert.
                $axe_alerts[] = $alert;
                
            }

        }

    }
    // Return alerts.
    return $axe_alerts;

}