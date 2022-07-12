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
 */
function process_integrations(){

    // Let's log our process for the CLI.
    echo "\n\n\n> Processing integrations...";

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');
    require_once(__ROOT__.'/models/adders.php');

    // This process is runs active integrations.
    $active_integrations = unserialize(
        DataAccess::get_meta_value('active_integrations')
    );

    // Let's log our progress for CLIs.
    $active_integrations_count = count(
        $active_integrations
    );
    echo "\n> $active_integrations_count active integration";
    if($active_integrations_count > 1 ){
        echo 's';
    }
    echo ':';

    // If there's no integrations ready to process, we
    // won't run the process.
    if(!empty($active_integrations)){

        // Now we run each integration.
        foreach ($active_integrations as $integration){

            // Let's log our progress and time for CLI.
            echo "\n>>> Running \"$integration.\"";
            $time_pre = microtime(true);

            // Every integration file is added using a
            // standard pattern.
            require_once 
                'integrations/'.$integration.'/functions.php';

            // We'll run each integration against meta we 
            // setup in process_site.php
            $scanable_pages = 
                unserialize(DataAccess::get_meta_value(
                    'scanable_pages'
                ))
            ;

            // No scanable pages means no need for the
            // integration process.
            if(!empty($scanable_pages)){

                // Each page is run against the
                // integration's functions.
                foreach ($scanable_pages as $page){
                
                    // Every integration use the same 
                    // pattern to run scan functions.
                    $integration_scan_function = 
                        $integration.'_scans';
                    if(
                        function_exists(
                            $integration_scan_function
                        )
                    ){
                        try {

                            // Do integration function.
                            $integration_scan_function(
                                $page
                            );

                        } catch (Exception $x) {

                            // Alert if an error occurs
                            // in the integration.
                            DataAccess::add_alert(
                                'system', $page, $integration, 
                                'error', $x->getMessage(), NULL
                            );

                        }
                    }

                }

            }

            // Log our progress.
            $time_post = microtime(true);
            $exec_time = $time_post - $time_pre;
            $alerts_count = number_format(
                DataAccess::count_db_rows('alerts')
            );
            echo "\n>>> Completed \"$integration\" in $exec_time seconds.";

        }
    }

}