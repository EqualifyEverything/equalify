<?php

/**
 * Uploaded Integrations
 */
function uploaded_integrations($path_to_integrations){

    // List all uploaded integrations.
    $integration_folders = array_diff(scandir($path_to_integrations), array('..', '.'));;
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