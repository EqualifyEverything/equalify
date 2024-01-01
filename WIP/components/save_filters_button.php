<?php 
// Adds a button to save unsaved filters.
function the_save_filters_button($report_id){
    // Construct the cookie name
    $cookie_name = "queue_report_" . $report_id . "_filter_change";

    // Check if the corresponding cookie exists and has a value
    if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]') {

        // Display the button only if the cookie exists
        ?>

        <a id="saveFiltersBtn" class="btn btn-primary" href="actions/save_report_filter_change.php?report_id=<?php echo $report_id; ?>">
            Save for Everyone
        </a>

        <?php
    }
}
?>
