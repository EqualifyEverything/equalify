<?php
// The tag_id URL parameter defines the tag.
$tag_id = $_GET['tag_id'];
if($tag_id == ''){
    throw new Exception(
        'tag_id is missing'
    );
}

// Optional report_id is used to add report data
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : ''; 

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/chart.php');
require_once('components/message_list.php');
require_once('components/report_header.php');

// Helpers
require_once('helpers/get_title.php');
require_once('helpers/get_report_filters.php');

// Ready filters for this view
$report_filters = get_report_filters()['as_string'];
parse_str($report_filters, $filters_array);
$filters_array['tags'] = $tag_id;
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
    echo get_title($tag_id, 'tag');
    ?>

    </h2>

<?php
// Chart component.
the_chart($new_report_filters);

// Message Occurrences
the_message_list($new_report_filters);
?>

</div>