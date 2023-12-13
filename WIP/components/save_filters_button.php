<?php 
// Adds a button to save unsaved filters.
function the_save_filters_button($report_id){
    // Construct the cookie name
    $cookie_name = "unsaved_report_" . $report_id . "_filters";

    // Check if the corresponding cookie exists and has a value
    if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]') {

        // Display the button only if the cookie exists
        ?>

        <a id="saveFiltersBtn" class="btn btn-primary" href="actions/save_unsaved_report_filters.php?report_id=<?php echo $report_id; ?>">
            Save Filters
        </a>

        <?php
    }
}
?>
