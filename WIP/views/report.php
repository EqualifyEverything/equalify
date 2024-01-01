<?php
// Temporary testing text
$report_id = 1;

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/title.php');
require_once('components/save_filters_button.php');
require_once('components/report_filter_search.php');
require_once('components/active_filters.php');
require_once('components/report_status_toggle.php');
require_once('components/chart.php');
require_once('components/message_list.php');
require_once('components/page_list.php');
require_once('components/tag_list.php');
require_once('components/save_filters_button.php');

// Lets get helpers we're using.
require_once('helpers/get_report_filters.php');

$report_filters = get_report_filters($pdo, $report_id);
?>
<div>
    Previewing Updates <a>Cancel Updates</a> <a>Save for Everyone</a>
</div>
<div class="d-flex flex-column flex-md-row align-items-center mt-4 mb-2">

    <?php
    // Page Title
    the_title($report_id, 'report');
    ?>

    <div class="ms-md-auto">

        <?php
        // Conditional save filters button
        the_save_filters_button($report_id);

        // Filter search component.
        the_report_filter_search($report_id);
        ?>

        <a class="btn btn-secondary" href="?view=report_settings&report_id=<?php echo $report_id;?>">
            Edit Report
        </a>

    </div>
</div>
<div class="row">
    <div class="col" id="active_filters">

        <?php
        // Filters component.
        the_active_filters($report_id, $report_filters['as_array']);
        ?>

    </div>
</div>

<?php
// Status toggle
the_report_status_toggle($report_id, $report_filters['as_string']);

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