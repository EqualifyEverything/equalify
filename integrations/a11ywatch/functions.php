<?php

/**
 * Name: A11yWatch
 * Description: Quick crawl, sitemap, and single page scans.
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

    // TODO: This is a temporary test of using error type as a tag
    $tags[] = [
        'title' => 'Error',
        'category' => 'Error Level',
        'description' => '',
        'slug' => 'error',
    ];
    $tags[] = [
        'title' => 'Warning',
        'category' => 'Error Level',
        'description' => '',
        'slug' => 'warning',
    ];
    $tags[] = [
        'title' => 'Notice',
        'category' => 'Error Level',
        'description' => '',
        'slug' => 'notice',
    ];


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
            'Transfer-Encoding' => 'chunked',
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
            'Transfer-Encoding' => 'chunked',
            'Authorization' => $auth_token
        ],
        'body' => json_encode([
            'url' => $page_url,
            "subdomains" => false,
            "sitemap" => 0,
            "tld" => false
        ]),
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
            'Transfer-Encoding' => 'chunked',
            'Authorization' => $auth_token,
        ],
        'body' => json_encode([
            'url' => $page_url,
            "subdomains" => false,
            "sitemap" => 1,
            "tld" => false
        ]),
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
    $scan_results = json_decode($response_body, true);

    // Empty/unparsable response.
    if (empty($scan_results)) {

        // Add a fallback alert
        $alert = [
            'source'  => 'a11ywatch',
            'url'     => $page_url,
            'message' => 'a11ywatch returned an empty or unparsable response.',
        ];
        $a11ywatch_alerts[] = $alert;

        return $a11ywatch_alerts;
    }

    // Generate and return alerts
    return alerts_from_a11ywatch_issues($scan_results, $page_url);
}

/**
 * A11yWatch Crawl Alerts
 * @param string response_body
 * @param string page_url
 */
function a11ywatch_crawl_alerts($response_body, $page_url)
{
    // Our goal is to return alerts.
    $a11ywatch_alerts = [];

    // Decode JSON.
    $scan_results = json_decode($response_body, true);

    // Empty/unparsable/non-array response.
    if (empty($scan_results) || !is_array($scan_results)) {
        $alert = [
            'source'  => 'a11ywatch',
            'url'     => $page_url,
            'message' => 'a11ywatch returned an empty or unparsable response.',
        ];
        $a11ywatch_alerts[] = $alert;

        return $a11ywatch_alerts;
    }

    // Loop through response array to generate alerts.
    // Expecting an entry for each page scanned.
    foreach ($scan_results as $scan_result) {
        $new_alerts = alerts_from_a11ywatch_issues($scan_result, $page_url);
        $a11ywatch_alerts = array_merge($a11ywatch_alerts, $new_alerts);
    }

    return $a11ywatch_alerts;
}

/**
 * A11yWatch Sitemap Alerts
 * @param string response_body
 * @param string page_url
 */
function a11ywatch_sitemap_alerts($response_body, $page_url)
{
    // Same format as crawl results.
    return a11ywatch_crawl_alerts($response_body, $page_url);
}

/**
 * Helper method to map a11ywatch issues for a page to equalify alerts
 * @param array scan_result
 * @param string page_url
 */
function alerts_from_a11ywatch_issues($scan_result, $page_url)
{

    // This array of alerts will be returned.
    $alerts = [];

    // Check that issues are where we expect in the response.
    $response_data = $scan_result['data'] ?? [];
    $url = $response_data['url'] ?? $page_url;

    // Add a parsing error fallback alert.
    $issues_found_in_response = array_key_exists('issues', $response_data);
    if (!$issues_found_in_response) {
        $alert = [
            'source'  => 'a11ywatch',
            'url'     => $url,
            'message' => 'Could not parse a11ywatch response.',
            'more_info' => json_encode($scan_result, JSON_PRETTY_PRINT),
        ];
        $alerts[] = $alert;

        return $alerts;
    }

    // Add all issues as alerts.
    $a11ywatch_issues = $response_data['issues'];
    foreach ($a11ywatch_issues as $issue) {
        $alert = [
            'source' => 'a11ywatch',
            'url' => $url,
            'tags' => $issue['type'], // type is error, warning, or notice
            'message' => $issue['message'],
            'more_info' => json_encode($issue, JSON_PRETTY_PRINT),
        ];

        $alerts[] = $alert;
    }

    return $alerts;
}
