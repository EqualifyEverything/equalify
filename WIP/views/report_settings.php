<?php
// The report_id URL parameter defines the page.
$report_id = $_GET['report_id'];
if($report_id == ''){
    throw new Exception(
        'report_id is missing'
    );
}

// Get helpers
require_once('helpers/get_title.php');
require_once('helpers/get_report_filters.php');

// Components
require_once('components/report_filter_search.php');
require_once('components/active_filters.php');

$report_filters = get_report_filters();


?>

<div class="container">
    <h1 class="display-5 mt-4" style="max-width:800px">
        <a href="?view=report&report_id=<?php echo $report_id;?>" class="link-dark link-underline link-underline-opacity-0 link-underline-opacity-75-hover">

            <?php
            // Page Title
            echo get_title($report_id, 'report');
            ?>

        </a>
    </h1>
    <h2 class="mb-4">Report Settings</h2>
    <div class="card my-2 p-4">
        <div class="pb-4 my-2">
            <h3 class="mb-4">General Settings</h3>
            <label for="reportName" class="form-label">Report Name</label>
            <input type="text" class="form-control form-control-lg" id="reportName" style="max-width: 400px" value="<?php echo get_title($report_id, 'report');?>">
        </div>
        <div class="border-top py-4 my-2">
            <h3 class="mb-4">Filters</h3>

            <?php
            // Active filters.
            the_active_filters($report_id, $report_filters['as_array']);

            // Filter search component.
            the_report_filter_search($report_id);
            ?>

        </div>
        <div class="border-top py-4 my-2">
            <h3 class="mb-4">Save Actions</h3>

            <?php
            // Unsaved changes update the state of a button
            $aria_disabled_state = true;
            $disabled_class = 'disabled';
            $cookie_name = "queue_report_" . $report_id . "_filter_change";
            if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]'){
                $aria_disabled_state = false;
                $disabled_class = '';    
            }
            ?>

            <a href="actions/delete_report_filter_cookie.php?report_id=<?php echo $report_id; ?>" class="btn btn-lg btn-outline-secondary <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                Cancel Updates
            </a> 
            <a href="?view=report&report_id=<?php echo $report_id?>" class="btn btn-lg btn-outline-primary <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                Preview Updates
            </a> 
            <a href="actions/save_report_filter_change.php?last_view=report_settings&report_id=<?php echo $report_id; ?>" class="btn btn-primary btn-lg <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                Save for Everyone
            </a>

        </div>
        <div class="border-top py-4">
            <h3 class="mb-4">Danger Zone</h3>
            <p>
                <a href="actions/delete_report.php" class="btn btn-lg btn-danger">Delete Report</a>
            </p>
        </div>
    </div>
</div>