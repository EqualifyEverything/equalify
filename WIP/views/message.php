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
    <div class="row">
        <div class="col-6">
            <div class="card p-4 h-100">
                <h3>AI Analysis <span class="badge rounded-pill ounded-pill text-bg-dark">Experimental</span></h3>
                <p>From available Equalify data on this message, lorem ipsum dolor sit amet, consectetur adipisicing elit. Praesentium aspernatur sint minima non, dolores consectetur veritatis eos iusto fugit totam nobis veniam officia modi accusantium amet sequi, incidunt porro iste.</p>
                <p class="text-body-secondary border-top pt-2"><strong>NOTE: This feature is experimental.</strong> We're testing how large language models interpret your scan results. <a href="#" class="link-secondary">Contact us</a> to let us know what you think.</p>
            </div>
        </div>
        <div class="col-6">

            <?php
            // Chart
            the_chart($new_report_filters);
            ?>

        </div>
    </div>

    <?php
    // Message Occurrences
    the_message_occurrences_list($new_report_filters);
    ?>

</div>