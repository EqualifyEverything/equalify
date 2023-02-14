<?php
/**
 * Name: A11yWatch
 * Description: The fastest and most precise automated accessibility scan.
 */

/**
 * A11yWatch Fields
 */
function a11ywatch_fields(){

    $a11ywatch_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Meta values.
                'meta' => [
                    array(
                        'name'     => 'a11ywatch_key',
                        'value'     => '',
                    )
                ]
            
        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'a11ywatch_key',
                    'label'    => 'a11ywatch JWT (ie- 2fa31242wdda23efsdaf342)',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $a11ywatch_fields;

}

/**
 * A11yWatch Tags
 */
function a11ywatch_tags(){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));
    
    // Read the JSON file - pulled from https://a11ywatch.webaim.org/api/docs?format=json
    $a11ywatch_tag_json = file_get_contents(__DIR__.'/a11ywatch_tags.json');
    $a11ywatch_tags = json_decode($a11ywatch_tag_json,true);

    // Convert a11ywatch format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if(!empty($a11ywatch_tags)){
        foreach($a11ywatch_tags as $a11ywatch_tag){

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">'.$a11ywatch_tag['description'].'</p>';
            
            // Now lets put it all together into the Equalify format.
            array_push(
                $tags, array(
                    'title' => $a11ywatch_tag['title'],
                    'category' => $a11ywatch_tag['category'],
                    'description' => $description,
                    'slug' => str_replace('.', '', $a11ywatch_tag['slug'])

                )
            );

        }
    }

    // Return tags.
    return $tags;

}

 /**
  * A11yWatch Crawl process start
  * Maps site URLs to Axe URLs for processing.
  */
function a11ywatch_urls($page_url) {

    // Lets specify everything Guzzle needs to create request.
    return array(
        'type' => 'POST',
        'url'  => 'https://api.a11ywatch.com/api/scan',
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'data' => array(
            'url' => $page_url
        )
    );
    
}

/**
 * A11yWatch Alerts
 * @param string response_body
 * @param string page_url
 */
function a11ywatch_alerts($response_body, $page_url){

    // Our goal is to return alerts.
    $a11ywatch_alerts = [];
    $a11ywatch_json = $response_body; 

    // Decode JSON.
    $a11ywatch_json_decoded = json_decode($a11ywatch_json);

    // Sometimes Axe can't read the json.
    if(empty($a11ywatch_json_decoded)){

        // And add an alert.
        $alert = array(
            'source'  => 'a11ywatch',
            'url'     => $page_url,
            'message' => 'a11ywatch cannot reach the page.',
        );
        array_push($a11ywatch_alerts, $alert);

    }else{

        // We're add a lit of violations.
        $a11ywatch_violations = array();

        // Show a11ywatch violations
        foreach($a11ywatch_json_decoded[0]->violations as $violation){

            // Only show violations.
            $a11ywatch_violations[] = $violation;

        }

        // Add alerts.
        if(!empty($a11ywatch_violations)) {

            // Setup alert variables.
            foreach($a11ywatch_violations as $violation){

                // Default variables.
                $alert = array();
                $alert['source'] = 'a11ywatch';
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
                        $alert['tags'].= str_replace('.', '', 'a11ywatch_'.$tag);
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
                $a11ywatch_alerts[] = $alert;
                
            }

        }

    }
    
    // Return alerts.
    return $a11ywatch_alerts;

}