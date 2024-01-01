<?php
// Temporary testing text
$report_id = 1;

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/title.php');
require_once('components/chart.php');
require_once('components/message_list.php');
require_once('components/page_list.php');
require_once('components/tag_list.php');
require_once('components/save_filters_button.php');

// Lets get helpers we're using.
require_once('helpers/get_report_filters.php');

$report_filters = get_report_filters($pdo, $report_id);
?>

<div class="d-flex flex-column flex-md-row align-items-center mt-4 mb-2">

    <?php
    // Page Title
    the_title($report_id, 'report');
    ?>

    <div class="ms-md-auto">

        <a class="btn btn-secondary" href="?view=report_settings&report_id=<?php echo $report_id;?>">
            Edit Settings
        </a>

    </div>
</div>

<div class="d-flex align-items-center justify-content-between p-4 my-2 fw-semibold text-success-emphasis bg-success-subtle border border-success-subtle rounded-2">
    Previewing Unsaved Report Settings
    <div>
        <a href="?view=report" class="btn btn-sm btn-outline-secondary">Cancel Updates</a> 
        
        <?php
        // Conditional save filters button
        the_save_filters_button($report_id, 'btn-sm');
        ?>
        
    </div>
</div>


<?php

// Chart component.
the_chart($report_filters['as_string']);

// Message List component.
the_message_list($report_filters['as_string']);
?>

<div class="row">
    <div class="col col-6">

        <?php
        // Tag component.
        the_tag_list($report_filters['as_string']);
        ?>

    </div>
    <div class="col col-6">

        <?php
        // Page list component.
        the_page_list($report_filters['as_string']);
        ?>

    </div>
</div>