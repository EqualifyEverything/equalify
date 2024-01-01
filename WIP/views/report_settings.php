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
require_once('components/save_filters_button.php');
require_once('components/report_filter_search.php');
require_once('components/active_filters.php');

$report_filters = get_report_filters($pdo, $report_id);


?>

<h1 class="my-4">Report Settings</h1>
<div class="card my-2 p-4">
    <div class="pb-4 my-2">
        <h2 class="mb-4">General Settings</h2>
        <label for="reportName" class="form-label">Report Name</label>
        <input type="text" class="form-control form-control-lg" id="reportName" style="max-width: 400px" value="<?php echo get_title($report_id, 'report');?>">
    </div>
    <div class="border-top py-4 my-2">
        <h2 class="mb-4">Filters</h2>

        <?php
        // Active filters.
        the_active_filters($report_id, $report_filters['as_array']);

        // Filter search component.
        the_report_filter_search($report_id);
        ?>

    </div>
    <div class="border-top py-4 my-2">
        <a href="?view=report" class="btn btn-lg btn-outline-secondary">Cancel Updates</a> 

        <?php
        // Conditional save filters button
        the_save_filters_button($report_id, 'btn-lg');
        ?>

        <a href="actions/delete_report.php" class="btn btn-lg btn-danger">Delete Report</a>
    </div>
</div>