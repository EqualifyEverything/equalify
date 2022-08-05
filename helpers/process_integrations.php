<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document runs integrations as they scan each site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Integrations
 * @param array sites_output
 */
function process_integrations(array $sites_output){

    // The goal of this process is to setup this array.
    $integrations_output = array(
        'processed_sources' => array(),
        'processed_sites'   => $sites_output[
            'processed_sites'
        ],
        'processed_urls'    => $sites_output[
            'processed_urls'
        ],
        'queued_alerts'     => array()
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

    // Let's add these active integrations to our output
    // array.
    $integrations_output['processed_sources'] = 
        $active_integrations;

    // We'll also log our progress for CLIs.
    $active_integrations_count = count(
        $active_integrations
    );
    $logged_progress = 
        "\n> $active_integrations_count active integration";
    if(
        ($active_integrations_count > 1)
        || ($active_integrations_count == 0)
    ){
        $logged_progress.='s';
    }
    $logged_progress.='.';
    update_scan_log($logged_progress);

    // If there's no integrations ready to process, we
    // won't run the process.
    if(!empty($active_integrations)){

        // Now we run each integration.
        foreach ($active_integrations as $integration){

            // Let's log our progress and time for CLI.
            update_scan_log(
                "\n>>> Running \"$integration\" against "
            );
            $time_pre = microtime(true);

            // Every integration file is added using a
            // standard pattern.
            require_once (
                __ROOT__.'/integrations/'.$integration
                .'/functions.php');

            // We'll run each integration against meta we 
            // setup in process_site.php
            $scannable_pages = $sites_output[
                'processed_urls'
            ];

            // No scannable pages means no need for the
            // integration process.
            if(!empty($scannable_pages)){

                // We'll use a count to change things
                // within the loop.
                $total_pages = count($scannable_pages);
                $count = 0;

                // Each page is run against the
                // integration's functions.
                foreach ($scannable_pages as $page){
                    $count++;

                    // Let's log the processed url.
                    $log_message = '';
                    if($total_pages != $count){
                        $log_message.= "\"$page\", ";
                    }elseif($total_pages == 1){
                        $log_message.= "\"$page\".";
                    }else{
                        $log_message.= "and \"$page\".";
                    }
                    update_scan_log($log_message);
                
                    // Every integration uses the same 
                    // pattern to return alerts.
                    $integration_alerts = 
                        $integration.'_alerts';
                    if(
                        function_exists(
                            $integration_alerts
                        )
                    ){
                        try {


                            // Let's see if new alerts are 
                            // created..
                            $new_alerts = $integration_alerts(
                                $page
                            );
                            if(!empty($new_alerts)){

                                // Add any new alerts into 
                                // the array.
                                foreach ($new_alerts as $alert){
                                    array_push(
                                        $integrations_output[
                                            'queued_alerts'
                                        ],
                                        $alert
                                    );
                                }

                            }

                            // Add urls to our output.
                            array_push(
                                $integrations_output[
                                    'processed_urls'
                                ],
                                $page
                            );

                        } catch (Exception $x) {

                            // Let's report that exception to
                            // CLI. 
                            $error_message = $x->getMessage();
                            update_scan_log(
                                "\n>>> $error_message"
                            );

                        }
                    }

                }

            }

            // Log our progress.
            $time_post = microtime(true);
            $exec_time = $time_post - $time_pre;
            update_scan_log(
                "\n>>> Completed \"$integration\" in $exec_time seconds."
            );

        }
    }

    // Finally, we return our hard work.
    return $integrations_output;

}