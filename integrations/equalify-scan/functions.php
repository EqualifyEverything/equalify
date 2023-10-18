<?php

/**
 * Name: Equalify Scan
 * Description: An automated accessibility scan.
 */

/**
 * Equalify Scan Tags
 */
function equalify_scan_tags()
{

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if (!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));

    // Read the JSON file - pulled from https://axe.webaim.org/api/docs?format=json
    $equalify_scan_tag_json = file_get_contents(__DIR__ . '/equalify_scan_tags.json');
    $equalify_scan_tags = json_decode($equalify_scan_tag_json, true);

    // Convert axe format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if (!empty($equalify_scan_tags)) {
        foreach ($equalify_scan_tags as $equalify_scan_tag) {

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">' . $equalify_scan_tag['description'] . '</p>';

            // Now lets put it all together into the Equalify format.
            array_push(
                $tags,
                array(
                    'title' => $equalify_scan_tag['title'],
                    'category' => $equalify_scan_tag['category'],
                    'description' => $description,

                    // equalify-scan uses periods, which get screwed up
                    // when equalify serializes them, so we're
                    // just not going to use periods
                    'slug' => str_replace('.', '', $equalify_scan_tag['slug'])

                )
            );
        }
    }

    // Return tags.
    return $tags;
}

/**
 * Equalify Scan request builder.
 * Maps site URLs to Scan URLs for processing.
 */
function equalify_scan_single_page_request($page_url)
{

    // Require equalify_scan_uri
    $equalify_scan_uri = DataAccess::get_meta_value('equalify_scan_uri');
    if (empty($equalify_scan_uri)) {
        throw new Exception('equalify-scan URI is not entered. Please add the URI in the integration settings.');
    } else {
        return [
            'method' => 'GET',
            'uri'  => $equalify_scan_uri . $page_url,
        ];
    }
}

/**
 * Equalify Scan Alerts
 * @param string response_body
 * @param string page_url
 */
function equalify_scan_single_page_alerts($response_body, $page_url)
{

    // Our goal is to return alerts.
    $equalify_scan_alerts = [];
    $equalify_scan_json = $response_body;

    // Decode JSON.
    $equalify_scan_json_decoded = json_decode($equalify_scan_json);

    // Sometimes Equalify Scan can't read the json.
    if (!empty($equalify_scan_json_decoded)) {

        // We add violations to this array.
        $equalify_scan_violations = array();

        // Show violations
        foreach ($equalify_scan_json_decoded[0]->violations as $violation) {

            // Only show violations.
            $equalify_scan_violations[] = $violation;
        }

        // Add alerts.
        if (!empty($equalify_scan_violations)) {

            // Setup alert variables.
            foreach ($equalify_scan_violations as $violation) {

                // Default variables.
                $alert = array();
                $alert['source'] = 'equalify_scan';
                $alert['url'] = $page_url;

                // Setup tags.
                $alert['tags'] = '';
                if (!empty($violation->tags)) {

                    // We need to get rid of periods so Equalify
                    // wont convert them to underscores and they
                    // need to be comma separated.
                    $tags = $violation->tags;
                    $copy = $tags;
                    foreach ($tags as $tag) {
                        $alert['tags'] .= str_replace('.', '', 'equalify_scan_' . $tag);
                        if (next($copy))
                            $alert['tags'] .= ',';
                    }
                }

                // Setup message.
                $alert['message'] = '"' . $violation->id . '" violation: ' . $violation->help;

                // Setup more info.
                $alert['more_info'] = '';
                if ($violation->nodes)
                    $alert['more_info'] = json_encode($violation->nodes, JSON_PRETTY_PRINT);

                // Push alert.
                $equalify_scan_alerts[] = $alert;
            }
        }
    }
    // Return alerts.
    return $equalify_scan_alerts;
}
