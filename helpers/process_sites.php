<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We use this document to process a site, so it's ready 
 * to be delivered to integrations. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Sites
 */
function process_sites(){

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');
    require_once(__ROOT__.'/models/adders.php');
    require_once(__ROOT__.'/helpers/scan.php');

    // The goal of this process is to set up this array.
    $sites_output = array();

    // Let's log our process for the CLI.
    update_scan_log("\n\n\n> Processing sites...");

    // We only run this process on active sites.
    $filtered_to_active_sites = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_sites = DataAccess::get_db_rows( 'scan_profiles',
        $filtered_to_active_sites, 1, 10000
    )['content'];

    // Log our progress for CLI.
    $active_sites_count = count($active_sites);
    echo "\n> $active_sites_count active site";
    if($active_sites_count > 1 ){
        echo 's';
    }
    echo ':';

    // We run this process if there are sites ready to
    // process.
    if(!empty($active_sites)){

        // Pages count is used for logging.
        $pages_count = 0;

        // Each site is processed individually.
        foreach($active_sites as $site){

            // Log our progress for CLI.
            update_scan_log("\n- $site->url ($site->type)");

            // Let's add a 'urls' array to our sites.
            $site->urls = array();

            // Every URL that is processed is added to
            // our output.
            $sites_output[$site->id] = $site;        

            // Processing a site means adding its 
            // site_pages as scannable_pages meta.
            $site_pages = single_page_adder(
                $site->url
            );

            // Finally, we'll save the output if there
            // are pages or destroy it if there are not.
            if(!empty($site_pages)){
                foreach ($site_pages as $page){
                    array_push( 
                        $sites_output[$site->id]->urls, $page
                    );
                }
            }else{
                unset($sites_output[$site->id]);
            }

            // We'll save the number of pages.
            $pages_count = $pages_count+count($site->urls);

        }

        // Without any pages, we can kill the scan.
        if($pages_count === 0)
            kill_scan("Your sites have no pages to scan.");

        // Update log for CLI.
        $formatted_pages_count = number_format($pages_count);
        update_scan_log("\n> Found $formatted_pages_count scan profiles.");
                
    // Without active sites, we kill the scan.
    }else{
        kill_scan('At least one scan profile needs to be active to run a scan.');
    }

    // Finally, we can return the values.
    return $sites_output;

}