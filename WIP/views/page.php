<?php
// The page_id URL parameter defines the page.
$page_id = $_GET['page_id'];
if($page_id == ''){
    throw new Exception(
        'page_id is missing'
    );
}

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/title.php');
require_once('components/chart.php');
require_once('components/page_occurrences_list.php');
require_once('components/chart.php');
require_once('components/report_header.php');

// Optional Report Header
if(!empty($_GET['report_id'])){
    the_report_header($_GET['report_id']);
}else{
    // Add some space before the content
    echo '<div class="mb-4"></div>';
}
?>

<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center my-4">

        <?php
        // Page Title
        the_title($page_id, 'page');
        ?>

    </div>

    <?php
    // Status toggle
    the_chart('pages='.$page_id);

    // Occurrence List
    the_occurrence_list('pages='.$page_id);
    ?>

</div>