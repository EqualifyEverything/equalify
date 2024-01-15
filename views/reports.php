<?php
// Helpers
require_once('helpers/get_reports.php');

// Components
require_once('components/success_or_error_message.php');
?>

<div class="container">

    <?php
    // Success or Error message
    the_success_or_error_message();
    ?>

    <div class="d-flex flex-column flex-md-row align-items-center my-4">
        <h1 class="display-5">Reports</h1>
        <div class="ms-md-auto">
            <a class="btn btn-primary" href="actions/create_report.php">New Report</a>
        </div>
    </div>
    <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-md-center">

    <?php
    // Show Scan Profiles
    $reports = get_reports();
    if( !empty($reports) ):
        foreach($reports as $report): 
    ?>

        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 card-title p-2 my-2">
                        <?php echo $report['report_title'];?>
                    </h2>
                    <a href="index.php?view=report&report_id=<?php echo $report['report_id'];?>" class="btn btn-secondary m-2">
                        View Report
                    </a>
                </div>
            </div>
        </div>

    <?php 
    // End Reports
    endforeach; 
    else: echo '<p class="lead text-center">No reports.</p>';
    endif;
    ?>

    </div>
</div>