<?php
// Temporary testing text
$report_id = 1;

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/report_header.php');
require_once('components/title.php');
require_once('components/chart.php');
require_once('components/message_list.php');
require_once('components/page_list.php');
require_once('components/tag_list.php');
require_once('components/save_filters_button.php');

// Lets get helpers we're using.
require_once('helpers/get_report_filters.php');

$report_filters = get_report_filters($report_id);

// Report Header
the_report_header($report_id);
?>

<div class="container mt-3">
    <h2 class="visually-hidden">Report Details</h2>

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
</div>