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

require_once('helpers/get_title.php');

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
    the_chart('pages='.$page_id);

    // Occurrence List
    the_occurrence_list('pages='.$page_id);
    ?>

</div>