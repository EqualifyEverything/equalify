<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document runs integrations as they scan each site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Since this file can run in the CLI, we must set the 
// directory if it isn't already set.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));

// Let's load in Composer.
require_once (__ROOT__.'/vendor/autoload.php');

// Let's run Guzzle.
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

    // The goal of this process is to set up this array.
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

    // Without active integrations, there's no reason to continue.
    if(empty($active_integrations))
        kill_scan("You have no active integrations.");

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

    // If there are no integrations ready to process, 
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

        // Let's log our progress and time for CLI.
        update_scan_log(
            "\n>>> Running \"$integration\" against scan profiles:\n"
        );
        $time_pre = microtime(true);

        // We'll run each integration against each site.
        foreach ($integrations_output['processed_sites'] as $site) {
            // Every integration needs to define two functions per supported scan type:
            // One to map site URLs to integration requests
            // Another to map integration responses to an array of Equalify alerts
            $scan_type = $site->type;
            $integration_request_builder = "${integration}_${scan_type}_request";
            $integration_alerts = "${integration}_${scan_type}_alerts";
            
            // Skip the integration if we fail to find either.
            if (!function_exists($integration_request_builder)) {
                update_scan_log(
                    "\n>>> WARNING: Request builder function for '$integration' integration not found for '$scan_type' scan type.\n"
                );
                continue;
            }
            if (!function_exists($integration_alerts)) {
                update_scan_log(
                    "\n>>> WARNING: Alerts function for '$integration' integration not found for '$scan_type' scan type.\n"
                );
                continue;
            }

            // set up in process_site.php
            $scannable_pages = $site->urls;

            // No scannable pages mean no need to run the integration.
            if (empty($scannable_pages)) {
                update_scan_log(
                    "\n>>> WARNING: No pages to scan for profile '$site->url'!\n"
                );
                continue;
            }
            
            $pool = build_integration_connection_pool(
                $site,
                $scannable_pages,
                $integration_request_builder,
                $integration_alerts, 
                $integrations_output
            );

            // Initiate the transfers and create a promise.
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

    // We can kill the process if no urls were processed
    // because we'll have nothing to add alerts for.
    if(empty($integrations_output['processed_urls']))
        kill_scan("Integrations processed no urls.");

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
    callable $integration_request_builder,
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
    $requests = function ($page_urls) use ($integration_request_builder) {

        foreach ($page_urls as $page_url) {
            $request_params = $integration_request_builder($page_url);
            $request = new Request(
                $request_params['method'] ?? 'GET',
                $request_params['uri'] ?? '', // this default should fail, with the error captured by $on_rejected
                $request_params['headers'] ?? [],
                $request_params['body'] ?? null,
            );

            // Yielding a key->value pair lets us specify the index for callbacks.
            // Using the original site's URL as the index here for logging purposes.
            yield $page_url => $request; 
            
        }
        
    };

    // Happy path: run the integration, log the index, and update the output
    $on_fulfilled = function (Response $response, $page_url) use ($site, $integration_alerts, &$output) {
        try {

            // Update log with URL
            update_scan_log("- $page_url ($site->type)\n");

            // Process any new alerts.
            $new_alerts = $integration_alerts(
                $response->getBody(), $page_url
            );
            if (!empty($new_alerts)) {
                
                foreach ($new_alerts as &$alert) {
                    // We need to add the site ID to all the alerts.
                    $alert['site_id'] = $site->id;

                    // Trim more_info if needed. 
                    // (current hardcoded limit is arbitrary)
                    if (array_key_exists('more_info', $alert)) {
                        if (strlen($alert['more_info']) > 3000) {
                            $alert['more_info'] = substr($alert['more_info'], 0, 2997).'...';
                        }
                    }
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
        // $error_message = "Error for $page_url: " . $reason->getHandlerContext();
        $error_message = "Error for $page_url: " . $reason->getMessage();
        update_scan_log("\n>>> $error_message\n");
    };

    return new Pool($client, $requests($page_urls), [
        'concurrency' => $GLOBALS['scan_concurrency'],
        'timeout' => $GLOBALS['scan_timeout'],
        'fulfilled' => $on_fulfilled,
        'rejected' => $on_rejected,
    ]);

}