<?php
// Require files to control db.
require '../config.php';
require '../models/db.php';
require '../models/adders.php';

// Since cron should only run when a scan
// is cued, we should get the next scan
$next_scan = DataAccess::get_next_scan();

// We only want to run scans that are scheduled
// after the current time (in the MySQL format).
$current_date = date('Y-m-d H:i:s');
if($current_date > $next_scan->time){

    // Do the next scan.
    scan($next_scan->id);

}else{

    // This fallback message is for testing purposes. This 
    // shouldn't be seen because scans are triggered via the
    // cron and cron events are only scheduled on times cued.
    throw new Exception('No scan scheduled');

}

/**
 * Scan
 */
function scan($scan_id){

    // Change status to 'running'.
    DataAccess::update_scan_status($scan_id, 'running');

    // Setup scanning_process and scanning_page meta here, since integrations 
    // and the system is going can update the meta value.
    $scanning_process_meta_filters = array(
        array(
            'name' => 'meta_name',
            'value' => 'scanning_process'
        ),
    );
    if(empty(DataAccess::get_meta($scanning_process_meta_filters))){
        DataAccess::add_meta('scanning_process');
    }
    $scanning_page_meta_filters = array(
        array(
            'name' => 'meta_name',
            'value' => 'scanning_page'
        )
    );
    if(empty(DataAccess::get_meta($scanning_page_meta_filters))){
        DataAccess::add_meta('scanning_page');
    }

    // We'll start by verifying site urls.
    DataAccess::update_meta_value('scanning_process', 'Verify site urls.');

    // We are only looking at active parents for now because if
    // their URLs don't work, their pages wont work.
    $filtered_to_active_sites = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_sites = DataAccess::get_sites($filtered_to_active_sites);
    if(!empty($active_sites)):
        $working_sites = [];
        foreach($active_sites as $site){

            // Set scanning_page so we can bug check if issues arrise.
            DataAccess::update_meta_value('scanning_page', $site);
            
            // Curl parent to determin if the site exists.
            $curl = curl_init($site);
            curl_setopt($curl, CURLOPT_URL, $site);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');

            // Execute CURL.
            $url_contents = curl_exec($curl);

            // Alert folks if site can't be reached.
            if($url_contents == false){
                DataAccess::add_alert('system', NULL, $site, NULL, 'error', 'Site is unreachable.', NULL);
            }else{

                // Parents that work are saved into a variable we
                // use for the next process.
                array_push($working_sites, $site);

            }
            
        }
    endif;

    // Sometimes we're offline or the URLs don't exist, so we need
    // to add a condition that skips all the rest of the processes
    // in that case.
    if(!empty($working_sites)):

        // working_urls will contain all the pages that we want
        // to run through integrations.
        $working_urls = [];

        // We need to curl all the working sites to get any new
        // pages before we launch into the scan.
        DataAccess::update_meta_value('scanning_process', 'Update site pages.');
        foreach ($working_sites as $site){

            // We need to clear out the scanning_page, since we're not
            // currently scanning a page.
            DataAccess::update_meta_value('scanning_page', '');

            // Set type before we delete all the pages.
            $type = DataAccess::get_site_type($site);

            // We'll need to delete pages and alerts of a site before 
            // we re-add the site.
            $filtered_by_site = array(
                array(
                    'name' => 'site',
                    'value' => $site
                )
            );
            DataAccess::delete_pages($filtered_by_site);
            $filtered_by_url = array(
                array(
                    'name' => 'url',
                    'value' => $site
                )
            );
            DataAccess::delete_alerts($filtered_by_url);
            
            // Use the adders to generate pages of sites again with
            // a fallback if any adder encounters an excemption.
            try{
                if($type == 'xml'){
                    xml_site_adder($site);
                    DataAccess::update_meta_value('scanning_process', 'Adding XML pages.');
                }
                if($type == 'wordpress'){
                    wordpress_site_adder($site);
                    DataAccess::update_meta_value('scanning_process', 'Adding WordPress pages.');
                }
                if($type == 'single_page'){
                    single_page_adder($site);
                    DataAccess::update_meta_value('scanning_process', 'Adding single pages.');
                }
            }
            catch(Exception $exemption){
                
                // Alert every site that cannot be scanned.
                DataAccess::add_alert('system', $site, $site, NULL, 'error', $exemption->getMessage(), NULL);
                DataAccess::update_scan_status($scan_id, 'incomplete');
                die;

            }

            // Now that all the pages are in the system, we can
            // create an array of working pages.
            $filtered_by_site = array(
                array(
                    'name' => 'site',
                    'value' => $site
                )
            );
            $pages = DataAccess::get_pages($filtered_by_site);
            foreach ($pages as $page){

                // Now we can log the page url again, since the scanner
                // is now directly running on it.
                DataAccess::update_meta_value('scanning_page', $page->url);

                // We'll use the $working_urls array later.
                array_push($working_urls, $page->url);

            }

        }

        // We'll need to set the pages_count before any integration
        // so that all scans are counted, even without integrations.
        $pages_count = 0;

        // With our working urls set, we can now run the integrations!
        $active_integrations = unserialize(DataAccess::get_meta_value('active_integrations'));
        foreach($active_integrations as $integration){
            require_once '../integrations/'.$integration.'/functions.php';

            // 'scanning_process' keeps track of what integration is running.
            DataAccess::update_meta_value('scanning_process', $integration);

            // Run integration scan on every working url.
            foreach ($working_urls as $url){
                $pages_count++;

                // Set scanning_page meta.
                DataAccess::update_meta_value('scanning_page', $url);

                // Every integration should use the same pattern to run
                // register thier scan functions.
                $integration_scan_function_name = $integration.'_scans';
                if(function_exists($integration_scan_function_name)){

                    // We need to kill the scan if there's an error.
                    try {
                        $integration_scan_function_name($url);

                    } catch (Exception $x) {

                        // We will kill the scan and alert folks of any errors, but
                        // we will also record the successful scans that occured.
                        $meta_usage_filters = array(
                            array(
                                'name' => 'meta_name',
                                'value' => 'usage'
                            ),
                        );
                        $existing_usage = DataAccess::get_meta($meta_usage_filters);
                        if(empty($existing_usage)){

                            // This might be the first time we run a scan, so 
                            // we need to create the meta field if none exists.
                            DataAccess::add_meta('usage', $pages_count);

                        }else{
                            DataAccess::update_meta_value('usage', $pages_count+$existing_usage[0]->meta_value);
                        }
                        DataAccess::add_alert('system', $url, $site, NULL, 'error', $x->getMessage(), NULL);
                        DataAccess::update_scan_status($scan_id, 'incomplete');
                        die;

                    }

                }

                // Successfully scanned pages get a timestamp.
                DataAccess::update_page_scanned_time($url);

            }

        }

        // We keep track of the amount of pages scanned.
        $meta_usage_filters = array(
            array(
                'name' => 'meta_name',
                'value' => 'usage'
            )
        );
        $existing_usage = DataAccess::get_meta($meta_usage_filters)[0]->meta_value;
        if(empty($existing_usage)){

            // This might be the first time we run a scan, so 
            // we need to create the meta field if none exists.
            DataAccess::add_meta('usage', $pages_count);

        }else{
            DataAccess::update_meta_value('usage', $pages_count+$existing_usage);
        }

        // Change status to 'complete'.
        DataAccess::update_scan_status($scan_id, 'complete');
        
    // End conditions related to working_urls
    endif;

    // Change status to 'complete'.
    DataAccess::update_scan_status($scan_id, 'complete');

    // Clear scan process and page after scan is complete.
    DataAccess::delete_meta('scanning_process');
    DataAccess::delete_meta('scanning_page');

}