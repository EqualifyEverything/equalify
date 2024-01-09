<?php
// The report_id URL parameter defines the page.
$report_id = $_GET['report_id'];
if($report_id == ''){
    throw new Exception(
        'report_id is missing'
    );
}

// Components
require_once('components/report_header.php');
require_once('components/chart.php');
require_once('components/message_list.php');
require_once('components/page_list.php');
require_once('components/tag_list.php');

// Helpers
require_once('helpers/get_report_filters.php');

$report_filters = get_report_filters()['as_string'];

// Report Header
the_report_header();
?>

<div class="container mt-3">
    <h2 class="visually-hidden">Report Details</h2>

    <?php

    // Chart component.
    the_chart($report_filters);

    // Message List component.
    the_message_list($report_filters);
    ?>

    <div class="row">
        <div class="col col-6">

            <?php
            // Tag component.
            the_tag_list($report_filters);
            ?>

        </div>
        <div class="col col-6">

            <?php
            // Page list component.
            the_page_list($report_filters);
            ?>

        </div>
    </div>
</div>