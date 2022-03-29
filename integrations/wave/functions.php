<?php
/**
 * Name: WAVE
 * Description: Counts WCAG 2.1 errors and links to page reports.
 * Status: Active
 */

/**
 * WAVE Fields
 */
function wave_fields(){

    // Set account fields.
    $fields = [
        array(
            'name'     => 'wave_key',
            'label'    => 'WAVE Account Key',
            'type'     => 'text',
            'required' => true
        )
    ];
    register_account_fields($fields);

    // Set property fields.
    $fields = [
        array(
            'field' => 'wave_wcag_2_1_errors',
            'label' => 'WAVE WCAG 2.1 Errors',
            'type'  => 'VARCHAR(20)'
        )
    ];
    register_property_fields($fields);

}

/**
 * WAVE Scans
 */
function wave_scans($property, $account){

    // Get WAVE page errors.
    $wave_url = 'https://wave.webaim.org/api/request?key='.$account['wave_key'].'&url='.$page_url['url'];
    $wave_json = file_get_contents($wave_url, false, stream_context_create($override_https));
    $wave_json_decoded = json_decode($wave_json, true);        
    $wave_errors = $wave_json_decoded['categories']['error']['count'];

    // Remove previously saved alerts.
    $alerts_filter = [
        array(
            'key'   =>  'id',
            'value' =>  $property['id']
        ),
        array(
            'key'   =>  'type',
            'value' =>  'wave_wcag_2_1_errors'
        )
    ];
    delete_alerts($alerts_filter);

    // Set optional alerts.
    if($wave_errors > 1)
        add_alert($property['id'], 'wave_wcag_2_1_errors', 'WCAG 2.1 page errors found! -WAVE');

    // Update property data.
    update_property_data($property['id'], 'wave_wcag_2_1_errors', $wave_errors);
        
}