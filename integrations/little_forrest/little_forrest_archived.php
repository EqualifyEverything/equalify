<?php
// Create Integration
// Note: file name must be unique because it functions as the URI
register_integration(
    array(

        // Integration Name (20 char. max) 
        'name'      => 'Little Forrest',

        // Tagline (60 Chararacter Max)
        'tagline'   => 'Counts WCAG 2.1 errors and links to reports.',

    ),
    array(
        
        // Toggle functions are .
        'toggle_functions'   => array('wcag_2_1_page_error'),

    )
);

/**
 * Scan properties with LF and return errors
 */
function wcag_2_1_page_error($properties){

    // Loop $properties
    foreach ($properties as &$property):
    
        // Get Little Forrest data.
        $little_forrest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.$property['url'].'&level=WCAG2AA';
        $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));

        // Fallback if LF scan doesn't work
        if(strpos($little_forrest_json, 'NoSuchFileException'))
            throw new Exception('Little Forrest error related to page "'.$little_forrest_url.'"');

        // Decode JSON and count WCAG errors.
        $little_forrest_json_decoded = json_decode($little_forrest_json, true);
        $little_forrest_errors = count($little_forrest_json_decoded['Errors']);
        if($little_forrest_errors == NULL)
            $little_forrest_errors = 0;

        // Update post meta.
        $property['wcag_errors'] = $little_forrest_errors;
    
    endforeach;

}

/**
 * Create Settings View
 */
function settings($properties){
?>

    <div class="form-check form-switch mb-3">
        <input type="hidden" name="little_forrest_wcag_2_1_page_error_alert" id="little_forrest_wcag_2_1_page_error_alert" value="<?php echo $account_info->little_forrest_wcag_2_1_page_error;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="little_forrest_wcag_2_1_page_error_alert_switch" <?php if($account_info->little_forrest_wcag_2_1_page_error == true) echo 'checked';?> >
        <label class="form-check-label" for="little_forrest_wcag_2_1_page_error_alert_switch">Alert WCAG 2.1 page errors via <a href="https://littleforest.co.uk/feature/web-accessibility/" target="_blank">Little Forrest's scan</a>.</label>
        <script>
        document.getElementById('little_forrest_wcag_2_1_page_error_alert_switch').addEventListener('change', function () {
            if ( this.checked ) {
                document.getElementById('little_forrest_wcag_2_1_page_error_alert').value = 1;
            } else {
                document.getElementById('little_forrest_wcag_2_1_page_error_alert').value = 0;
            }
        });
        </script>
    </div>

<?php
}