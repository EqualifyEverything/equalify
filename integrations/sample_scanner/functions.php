<?php

/**
 * Name: Sample Scanner
 * Description: Run the Equalify Sample Scanner.
 */

/**
 * Default Scan Fields
 */
function sample_scanner_fields()
{

    $sample_scanner_fields = array(

        // These fields are added to the database.
        'db' => [

            // Meta values.
            'meta' => [
                array(
                    'name'     => 'sample_scanner_uri',
                    'value'     => '',
                )
            ]

        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'sample_scanner_uri',
                    'label'    => 'Sample-scan URI (ie- https://scan.Sample.app/?url=)',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $sample_scanner_fields;
}

/**
 * Sample Scan Tags
 */
function sample_scanner_tags()
{

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if (!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));

    // Read the JSON file - pulled from https://axe.webaim.org/api/docs?format=json
    $sample_scanner_tag_json = file_get_contents(__DIR__ . '/sample_scanner_tags.json');
    $sample_scanner_tags = json_decode($sample_scanner_tag_json, true);

    // Convert axe format into Sample format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if (!empty($sample_scanner_tags)) {
        foreach ($sample_scanner_tags as $sample_scanner_tag) {

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">' . $sample_scanner_tag['description'] . '</p>';

            // Now lets put it all together into the Sample format.
            array_push(
                $tags,
                array(
                    'title' => $sample_scanner_tag['title'],
                    'category' => $sample_scanner_tag['category'],
                    'description' => $description,

                    // Sample-scan uses periods, which get screwed up
                    // when Sample serializes them, so we're
                    // just not going to use periods
                    'slug' => str_replace('.', '', $sample_scanner_tag['slug'])

                )
            );
        }
    }

    // Return tags.
    return $tags;
}

/**
 * Sample Scan request builder.
 * Maps site URLs to Scan URLs for processing.
 */
function sample_scanner_single_page_request($page_url)
{

    // Require sample_scanner_uri - if you don't already have a scanner built,
    // checkout https://github.com/EqualifyEverything/Sample-Scanner
    $sample_scanner_uri = DataAccess::get_meta_value('sample_scanner_uri');
    if (empty($sample_scanner_uri)) {
        throw new Exception('Sample-scan URI is not entered. Please add the URI in the integration settings.');
    } else {
        return [
            'method' => 'GET',
            'uri'  => $sample_scanner_uri . $page_url,
        ];
    }
}

/**
 * Sample Scan Notices
 * @param string response_body
 * @param string page_url
 */
function sample_scanner_single_page_notices($response_body, $page_url)
{

    // Our goal is to return notices.
    $sample_scanner_notices = [];
    $sample_scanner_json = $response_body;

    // Decode JSON.
    $sample_scanner_json_decoded = json_decode($sample_scanner_json);

    // Sometimes Sample Scan can't read the json.
    if (!empty($sample_scanner_json_decoded)) {

        // We add violations to this array.
        $sample_scanner_violations = array();

        // Show violations
        foreach ($sample_scanner_json_decoded[0]->violations as $violation) {

            // Only show violations.
            $sample_scanner_violations[] = $violation;
        }

        // Add notices.
        if (!empty($sample_scanner_violations)) {

            // Setup notice variables.
            foreach ($sample_scanner_violations as $violation) {

                // Default variables.
                $notice = array();
                $notice['source'] = 'sample_scan';
                $notice['url'] = $page_url;

                // Setup tags.
                $notice['tags'] = '';
                if (!empty($violation->tags)) {

                    // We need to get rid of periods so Sample
                    // wont convert them to underscores and they
                    // need to be comma separated.
                    $tags = $violation->tags;
                    $copy = $tags;
                    foreach ($tags as $tag) {
                        $notice['tags'] .= str_replace('.', '', 'sample_scanner_' . $tag);
                        if (next($copy))
                            $notice['tags'] .= ',';
                    }
                }

                // Setup message.
                $notice['message'] = '"' . $violation->id . '" violation: ' . $violation->help;

                // Setup more info.
                $notice['more_info'] = '';
                if ($violation->nodes)
                    $notice['more_info'] = json_encode($violation->nodes, JSON_PRETTY_PRINT);

                // Push notice.
                $sample_scanner_notices[] = $notice;
                
            }
        }
    }
    // Return notices.
    return $sample_scanner_notices;
}
