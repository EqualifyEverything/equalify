<?php

/**
 * Uploaded Integrations
 */
function uploaded_integrations(){

    // List all uploaded integrations.
    $itegration_path = '../integrations';
    $integration_folders = array_diff(scandir($itegration_path), array('..', '.'));;
    $uploaded_integrations = [];
    foreach ($integration_folders as $integration_folder){

        // Create URI from folder name.
        array_push(
            $uploaded_integrations, 
            array(
                'uri' => $integration_folder
            )
        );

    }
    return $uploaded_integrations;

}