<?php
// The page_id URL parameter defines the page.
$message_id = $_GET['message_id'] ;
if($message_id == '')
    throw new Exception(
        'message_id is missing'
    );

// Optional report_id is used to add report data
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : ''; 

// Let's get the various components we need to create the view.
require_once('components/report_header.php');
require_once('components/chart.php');
require_once('components/message_occurrences_list.php');

// Helpers
require_once('helpers/get_report_filters.php');

// Ready filters for this view
$report_filters = get_report_filters()['as_string'];
parse_str($report_filters, $filters_array);
$filters_array['messages'] = $message_id;
$new_report_filters = http_build_query($filters_array);

// Optional Report Header
if(!empty($report_id)){
    the_report_header();
}else{
    // Add some space before the content
    echo '<div class="mb-4"></div>';
}
?>

<div class="container">
    <div class="d-flex falign-items-start mb-2">
        <h2 class="me-2">

            <?php
            // Page Title
            echo get_title($message_id, 'message');
            ?>

        </h2>
        <div>
            <a class="btn btn-primary" href="actions/create_report.php">
                More Info 
                <span class="visually-hidden">
                    Link opens in new tab
                </span>
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
                </svg>
            </a>
        </div>
    </div>

    <?php
    // Chart
    the_chart($new_report_filters);

    // Message Occurrences
    the_message_occurrences_list($new_report_filters);
    ?>

</div>