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
<div class="d-flex falign-items-start align-items-center mb-4">
        <h2 class="me-2 mb-0">

        <?php
        // Page Title
        echo get_title($page_id, 'page');
        ?>

        </h2>
        <div>
        
        <?php
        // Add session data to scan page.
        session_start();    
        $_SESSION['page_id'] = $page_id
        ?>

            <a href="actions/rescan_page.php?report_id=<?php echo $report_id?>" class="btn btn-primary btn-sm my-0">
                Rescan Page
            </a>

        </div>
    </div>
    
    <?php
    // Status toggle
    the_chart($new_report_filters);

    // Occurrence List
    the_occurrence_list($new_report_filters);
    ?>

</div>