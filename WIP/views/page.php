<?php
// The page_id URL parameter defines the page.
$page_id = $_GET['page_id'];
if($page_id == ''){
    throw new Exception(
        'page_id is missing'
    );
}

// Optional report_id is used to add report data
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : ''; 

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/chart.php');
require_once('components/page_occurrences_list.php');
require_once('components/chart.php');
require_once('components/report_header.php');

// Helpers
require_once('helpers/get_title.php');
require_once('helpers/get_report_filters.php');

// Ready filters for this view
$report_filters = get_report_filters()['as_string'];
parse_str($report_filters, $filters_array);
$filters_array['pages'] = $page_id;
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
    echo get_title($page_id, 'page');
    ?>

    </h2>

    <?php
    // Status toggle
    the_chart($new_report_filters);

    // Occurrence List
    the_occurrence_list($new_report_filters);
    ?>

</div>