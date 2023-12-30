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

?>

<h1 class="my-4">Report Settings</h1>
<div class="card my-2 p-4">
    <form action="actions/update_report.php">
        <div class="row my-4">
            <div class="col">
                <label for="reportName" class="form-label">Report Name</label>
                <input type="text" class="form-control form-control-lg" id="reportName" style="max-width: 400px" value="<?php echo get_title($report_id, 'report');?>">
            </div>
        </div>
        <button class="my-4 btn btn-primary">Save Settings</button>  <a href="actions/delete_report.php" class="btn btn-outline-danger">Delete Report</a>
    </form>
</div>