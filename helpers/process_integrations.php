<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document runs integrations as they scan each site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Process Integrations
 * @param array sites_output
 */
function process_integrations(array $sites_output){

    // The goal of this process is to setup this array.
    $integrations_output = array(
        'processed_sources' => array(),
        'processed_urls'    => array(),
        'processed_sites'   => $sites_output
    );

    // Let's log our process for the CLI.
    update_scan_log("\n\n\n> Processing integrations...");

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // This process runs active integrations.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );

    // Let's add these active integrations to our output array.
    $integrations_output['processed_sources'] = $active_integrations;

    // We'll also log our progress for CLIs.
    $active_integrations_count = count($active_integrations);

    $logged_progress = "\n> $active_integrations_count active integration";
    if ($active_integrations_count !== 1) {
        $logged_progress.='s';
    }
    $logged_progress.='.';
    update_scan_log($logged_progress);

    // If there's no integrations ready to process, 
    // we won't run the process.
    if (empty($active_integrations)) {
        return $integrations_output;
    }

    // Now we run each integration.
    foreach ($active_integrations as $integration){

        // Every integration file is added using a standard pattern.
        require_once (
            __ROOT__.'/integrations/'.$integration.'/functions.php'
        );

        // (NOTE: A more robust integration interface might use a request generator
        // function instead of a simple URL mapping; current integrations
        // don't need this and just GET with Guzzle's default headers.)

        // Every integration needs to define two functions:
        // One to map site URLs to integration request URLs
        $integration_urls = $integration.'_urls';
        // Another to map integration responses to an array of Equalify alerts
        $integration_alerts = $integration.'_alerts';
        
        // Skip the integration if we fail to find either.
        if (!function_exists($integration_urls)) {
            update_scan_log(
                "\n>>> ERROR: URL mapping function for Integration " . 
                "'$integration' not found.\n"
            );
            continue;
        }
        if (!function_exists($integration_alerts)) {
            update_scan_log(
                "\n>>> ERROR: Alerts function for Integration " . 
                "'$integration' not found.\n"
            );
            continue;
        }

        // Let's log our progress and time for CLI.
        update_scan_log(
            "\n>>> Running \"$integration\" against pages: \n"
        );
        $time_pre = microtime(true);

        // We'll run each integration against each site.
        foreach ($integrations_output['processed_sites'] as $site) {

            // setup in process_site.php
            $scannable_pages = $site->urls;

            // No scannable pages means no need to run the integration.
            if (empty($scannable_pages)) {
                continue;
            }
            
            $pool = build_integration_connection_pool(
                $site,
                $scannable_pages,
                $integration_urls,
                $integration_alerts, 
                $integrations_output
            );

            // Initiate the transfers and create a promise
            $integration_run = $pool->promise();

            // Force the pool of requests to complete.
            $integration_run->wait();

            // Add urls to our output.
            $integrations_output['processed_urls'] += $scannable_pages;
        }

        // Log our progress.
        $time_post = microtime(true);
        $exec_time = $time_post - $time_pre;
        update_scan_log(
            ">>> Completed \"$integration\" in $exec_time seconds."
        );

    }

    // Finally, we return our hard work.
    return $integrations_output;

}

/**
 * Integrations are expected to provide:
 * - a function that maps site URLs to integration URLs.
 * - a function that maps integration outputs to Equalify's output model
 * 
 * This function adapts the integration function to work with a connection pool 
 * so we can make concurrent requests.
 * 
 * Returns a connection pool for a Guzzle client that will:
 * - run the integration function on each page 
 * - update the $output variable
 * - report successes and failures to the scan log
 */
function build_integration_connection_pool(
    $site,
    array $page_urls, 
    callable $integration_urls,
    callable $integration_alerts,
    array &$output
) {

    // No SSL verification for now
    $client = new Client(['verify' => false]);

    // NOTE: need to batch DB inserts, because this happened:
    // Failed: Prepared statement contains too many placeholders
    // ^ and that was with alerts from 100 pages
    // for comparison: pantheon had ~3k alerts from 10 pages
    
    // Makes sense to stop the pool and bulk insert before that
    // anyway to keep memory usage low (and give the integration
    // servers a bit of time to catch their breath).
    
    // Request generator
    $requests = function ($page_urls) use ($integration_urls) {

        // NOTE: for testing, keep a low maximum.
        $limit = $GLOBALS['page_limit'];
        $current = 0;

        foreach ($page_urls as $page_url) {

            // Map site URL to integration-specific URL
            $integration_url = $integration_urls($page_url);
            $request = new Request('GET', $integration_url);

            // Yielding a key->value pair lets us specify the index for callbacks.
            // Using the original site's URL as the index here for logging purposes.
            yield $page_url => $request; 
            
            $current++;
            if ($current >= $limit) break;
        }
    };

    // Happy path: run the integration, log the index, and update the output
    $on_fulfilled = function (Response $response, $page_url) use ($site, $integration_alerts, &$output) {
        try {

            // Update log with URL
            update_scan_log("'$page_url'\n");

            // Process any new alerts.
            $new_alerts = $integration_alerts(
                $response->getBody(), $page_url
            );
            if (!empty($new_alerts)) {
                
                // We need to add the site ID to all the alerts.
                foreach ($new_alerts as &$alert) {
                    $alert['site_id'] = $site->id;
                }

                // Now let's queue the alerts.
                DataAccess::add_db_rows(
                    'queued_alerts', $new_alerts
                );
            
            }

        } catch (Exception $x) {

            // Let's report that exception. 
            $error_message = $x->getMessage();
            update_scan_log("\n>>> $error_message\n");

        }
    };

    // Sad path: log the transfer error
    $on_rejected = function (RequestException $reason, $page_url) {
        $error_message = "Error for $page_url: " . $reason->getHandlerContext();
        update_scan_log("\n>>> $error_message\n");
    };

    return new Pool($client, $requests($page_urls), [
        'concurrency' => 20, // NOTE: Might want this to be a config value
        'fulfilled' => $on_fulfilled,
        'rejected' => $on_rejected,
    ]);

}