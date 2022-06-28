<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document to integrations as they scan each site.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// This document is going to use the DB.
require_once 'config.php';
require_once 'models/db.php';

// `integrations_processing` meta records what 
// integrations are running or left to run.
$integrations_processing = unserialize(
    DataAccess::get_meta_value('integrations_processing')
);

// Now we run each integration.
if(!empty($integrations_processing)){
    foreach ($integrations_processing as $integration){

        // Every integration file is added using a
        // standard pattern.
        require_once 
            'integrations/'.$integration.'/functions.php';

        // We'll run each integration against pages that
        // the integration hasn't scanned.
        $scanable_pages = 
            unserialize(DataAccess::get_meta_value(
                'scanable_pages'
            ))
        ;
        if(!empty($scanable_pages)){
            foreach ($scanable_pages as $page){
            
                // Every integration should use the same 
                // pattern to run thier scan functions.
                $integration_scan_function = $integration.'_scans';
                if(function_exists($integration_scan_function)){
                    try {

                        // Do integration function.
                        $integration_scan_function(
                            $page
                        );

                    } catch (Exception $x) {

                        // We will alert folks when an error
                        // occurs.
                        DataAccess::add_alert(
                            'system', $page, $integration, 
                            'error', $x->getMessage(), NULL
                        );

                    }
                }

            }

        }

        // Once there's no more unscanned pages, we can 
        // remove the integration from the array.
        unset($integrations_processing[0]);
        $integrations_processing_reset = 
            array_values($integrations_processing);
        DataAccess::update_meta_value( 
            'integrations_processing', 
            serialize($integrations_processing_reset)
        );

        // Now we can run the process again - we want to 
        // limit the length of the process and a curl of 
        // every site  page can be a cumbersome process 
        // that drags down on  slower servers.
        shell_exec(
            $GLOBALS['PHP_PATH'].
            ' cli/process_integration.php'
        );
        die;

    }
}