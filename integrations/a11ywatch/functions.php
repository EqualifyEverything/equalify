<?php

/**
 * Name: A11yWatch
 * Description: The fastest and most precise automated accessibility scan.
 */

/**
 * A11yWatch Fields
 */
function a11ywatch_fields()
{

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
function a11ywatch_tags()
{

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if (!defined('__DIR__'))
        define('__DIR__', dirname(dirname(__FILE__)));

    // Read the JSON file - pulled from https://a11ywatch.webaim.org/api/docs?format=json
    $a11ywatch_tag_json = file_get_contents(__DIR__ . '/a11ywatch_tags.json');
    $a11ywatch_tags = json_decode($a11ywatch_tag_json, true);

    // Convert a11ywatch format into Equalify format:
    // tags [ array('slug' => $value, 'name' => $value, 'description' => $value) ]
    $tags = array();
    if (!empty($a11ywatch_tags)) {
        foreach ($a11ywatch_tags as $a11ywatch_tag) {

            // First, let's prepare the description, which is
            // the summary and guidelines.
            $description = '<p class="lead">' . $a11ywatch_tag['description'] . '</p>';

            // Now lets put it all together into the Equalify format.
            array_push(
                $tags,
                array(
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
 * A11yWatch scan process request.
 * Maps site URLs to A11yWatch API requests for processing.
 */
function a11ywatch_single_page_request($page_url)
{
    // Lets specify everything Guzzle needs to create request.
    $auth_token = DataAccess::get_meta_value('a11ywatch_key');
    return [
        'method' => 'POST',
        'uri'  => 'https://api.a11ywatch.com/api/scan',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $auth_token
        ],
        'body' => json_encode(['url' => $page_url])
    ];
}

/**
 * A11yWatch crawl process request.
 * Maps site URLs to A11yWatch API requests for processing.
 */
function a11ywatch_crawl_request($page_url)
{
    // Lets specify everything Guzzle needs to create request.
    $auth_token = DataAccess::get_meta_value('a11ywatch_key');
    return [
        'method' => 'POST',
        'uri'  => 'https://api.a11ywatch.com/api/crawl',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $auth_token
        ],
        'body' => [
            'url' => $page_url
        ]
    ];
}

/**
 * A11yWatch sitemap process request.
 * Maps site URLs to A11yWatch API requests for processing.
 */
function a11ywatch_sitemap_request($page_url)
{
    // Lets specify everything Guzzle needs to create request.
    $auth_token = DataAccess::get_meta_value('a11ywatch_key');
    return [
        'method' => 'POST',
        'uri'  => 'https://api.a11ywatch.com/api/crawl',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $auth_token,
        ],
        'body' => [
            'url' => $page_url,
            'sitemap' => 1
        ]
    ];
}


/**
 * A11yWatch Alerts
 * @param string response_body
 * @param string page_url
 */
function a11ywatch_single_page_alerts($response_body, $page_url)
{

    // Our goal is to return alerts.
    $a11ywatch_alerts = [];
    
    // Decode JSON.
    $a11ywatch_json = $response_body;
    $a11ywatch_json_decoded = json_decode($a11ywatch_json, true);

    // Empty/unparsable response.
    if (empty($a11ywatch_json_decoded)) {

        // Add a fallback alert.
        $alert = [
            'source'  => 'a11ywatch',
            'url'     => $page_url,
            'message' => 'a11ywatch cannot reach the page.',
        ];
        $a11ywatch_alerts[] = $alert;

        return $a11ywatch_alerts;
    }


    // Check that issues are where we expect in the response
    $response_data = $a11ywatch_json_decoded['data'] ?? [];
    $issues_found_in_response = array_key_exists('issues', $response_data);
    if (!$issues_found_in_response) {

        // Add a fallback alert - arguably this is a parsing error
        $alert = [
            'source'  => 'a11ywatch',
            'url'     => $page_url,
            'message' => 'Could not parse a11ywatch response.',
        ];
        $a11ywatch_alerts[] = $alert;

        return $a11ywatch_alerts;
    }

    // Add all issues as alerts
    $a11ywatch_issues = $response_data['issues'];

    foreach ($a11ywatch_issues as $issue) {

        // Build alert from issue
        $alert = [
            'source' => 'a11ywatch',
            'url' => $page_url,
            'tags' => $issue['type'], // type is error, warning, or notice
            'message' => $issue['message'],
        ];

        // Add code, context, and recurrence to more_info
        $code = $issue['code'];
        $context = $issue['context'];
        $recurrence = $issue['recurrence'];
        $alert['more_info'] = "Code: '$code'. Context: '$context'. Recurrence: '$recurrence'.";

        // Push to alerts array
        $a11ywatch_alerts[] = $alert;
    }

    // Return alerts.
    return $a11ywatch_alerts;
}

// TODO: Alert formatters for crawl and sitemap scan types
