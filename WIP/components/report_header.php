<?php
// Get helpers
require_once('helpers/get_title.php');

// Get related components
require_once('components/save_filters_button.php');

// Creates a list of pages with the percent equalfied.
function the_report_header($report_id)
{
?>

<div class="pb-2 my-0 ">
    <div class="fw-semibold text-info-emphasis bg-info-subtle border-bottom border-info">
        <div class="container d-flex align-items-center justify-content-between py-4 ">
            Previewing Unsaved Report Settings
            <div>
                <a href="?view=report" class="btn btn-sm btn-outline-secondary">Cancel Updates</a> 
                
                <?php
                // Conditional save filters button
                the_save_filters_button($report_id, 'btn-sm');
                ?>
                
            </div>
        </div>
    </div>
    <div class="d-flex flex-column flex-md-row align-items-center mt-4 container">

        <h1 style="max-width:800px">

        <?php
        // Page Title
        echo get_title($report_id, 'report');
        ?>

        </h1>

        <div class="ms-md-auto">

            <a class="btn btn-secondary" href="?view=report_settings&report_id=<?php echo $report_id;?>">
                Edit Settings
            </a>

        </div>
    </div>
</div>

<?php
}