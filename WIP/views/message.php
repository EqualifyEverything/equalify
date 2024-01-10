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
    <h2 class="mb-4" style="max-width:900px">

    <?php
    // Page Title
    echo get_title($message_id, 'message');
    ?>

    </h2>

    <?php
    // Chart
    the_chart($new_report_filters);

    // Message Occurrences
    the_message_occurrences_list($new_report_filters);
    ?>

</div>