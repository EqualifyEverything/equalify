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

// Components
require_once('components/save_filters_button.php');
require_once('components/report_filter_search.php');
require_once('components/active_filters.php');


?>

<h1 class="my-4">Report Settings</h1>
<div class="card my-2 p-4">
    <h2 class="my-4">Global Settings</h2>
    <form action="actions/update_report.php">
        <div class="row my-4">
            <div class="col">
                <label for="reportName" class="form-label">Report Name</label>
                <input type="text" class="form-control form-control-lg" id="reportName" style="max-width: 400px" value="<?php echo get_title($report_id, 'report');?>">
            </div>
        </div>
        <?php
        // Filter search component.
        the_report_filter_search($report_id);

        // Active filters.
        the_active_filters($report_id, $report_filters['as_array']);

        // Conditional save filters button
        the_save_filters_button($report_id);
        ?>

        <a href="?view=report" class="btn btn-outline-secondary">Cancel Updates</a> 

    </form>
</div>
<div class="card my-2 p-4">
    <h2 class="my-4">Visibility</h2>
    <a href="actions/delete_report.php" class="btn btn-danger">Delete Report</a>
</div>