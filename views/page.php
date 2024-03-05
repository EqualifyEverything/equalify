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
require_once('helpers/get_content.php');
require_once('helpers/get_report_filters.php');
require_once('helpers/is_page_scanning.php');

// Set info if page is currently being scanned
$is_page_scanning = is_page_scanning($page_id);
if($is_page_scanning){

    // Success message notifies that page is scanning
    $_SESSION['success'] = 'Page is queued for scanning. Reload page to check status.';

}

// Ready filters for this view
$report_filters = get_report_filters()['as_string'];
parse_str($report_filters, $filters_array);
$filters_array['pages'] = $page_id;
$new_report_filters = http_build_query($filters_array);

// The content
$the_content = get_content('pages', $page_id);
$page_url = $the_content->page_url;
$page_property_id = $the_content->page_property_id;

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
            <span class="visually-hidden">Page: </span>
        <?php
        // Page Title
        echo $page_url;
        ?>

        </h2>
        <div>
        
        <?php
        // Make button hidden if page is scanning.
        if($is_page_scanning){
            echo '<a href="actions/process_page.php" class="btn btn-primary btn-sm my-0 disabled" tabindex="-1" role="button" aria-disabled="true">Send to Scan</a>';
        }else{

            // Session data is required to scan page.
            $page_data = array(
                'property_id' => $page_property_id,
                'page_id' => $page_id,
                'page_url' => $page_url
            );
            if($report_id) // Report IDs can be blank
                $page_data['report_id'] = $report_id;
            $_SESSION['process_this_page'] = $page_data;
            
            echo '<a href="actions/process_page.php" class="btn btn-primary btn-sm my-0" role="button">Send to Scan</a>';
        }
        ?>

        </div>
    </div>
    
    <?php
    // Status toggle
    the_chart($new_report_filters);

    // Occurrence List
    the_occurrence_list($new_report_filters);
    ?>

</div>