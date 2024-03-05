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
                        View <span class="visually-hidden"><?php echo $report['report_title'];?></span> Report
                    </a>
                </div>
            </div>
        </div>

    <?php 
    // End Reports
    endforeach; 
    else: echo '<div class="text-center"><p class="lead text-center">No reports.</p><div class="mt-4 bg-secondary-subtle p-4 border border-dark-subtle rounded"><h2 class="h4">New to Equalify?</h2>Checkout <a href="https://www.youtube.com/watch?v=g3t49qSIc-0">this video</a> to get started.</div></div>';
    endif;
    ?>

    </div>
</div>