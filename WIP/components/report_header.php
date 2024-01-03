<?php
// Get helpers
require_once('helpers/get_title.php');

// Creates a list of pages with the percent equalfied.
function the_report_header() {
    global $report_id;
?>

<div class="pb-2 my-0 ">

    <?php
    // Add notice when there's unsaved schanges
    $cookie_name = "queue_report_" . $report_id . "_filter_change";
    if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]'):
    ?>

    <div class="fw-semibold text-info-emphasis bg-info-subtle border-bottom border-info">
        <div class="container d-flex align-items-center justify-content-between py-4 ">
            Previewing Unsaved Report Settings
            <div>
            <a href="?view=report&report_id=<?php echo $report_id?>" class="btn btn-sm btn-outline-secondary">
                Cancel Updates
            </a> 
            <a href="actions/save_report_filter_change.php?report_id=<?php echo $report_id; ?>&last_view=<?php $_GET['view'];?>" class="btn btn-primary btn-sm">
                Save for Everyone
            </a>

                
            </div>
        </div>
    </div>

    <?php
    // End unsaved changes notice
    endif;
    ?>

    <div class="d-flex flex-column flex-md-row align-items-center mt-4 container">

        <h1 class="display-5" style="max-width:800px">
            <a href="?view=report&report_id=<?php echo $report_id;?>" class="link-dark link-underline link-underline-opacity-0 link-underline-opacity-75-hover">

                <?php
                // Page Title
                echo get_title($report_id, 'report');
                ?>

            </a>
        </h1>

        <div class="ms-md-auto">

            <a class="btn btn-secondary" href="?view=report_settings&report_id=<?php echo $report_id;?>">
                Edit Report
            </a>

        </div>
    </div>
</div>

<?php
}