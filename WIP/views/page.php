<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the page view, which shows info
 * related to pages saved in Equalify.
 * 
 * "Equalify" means fixing accessibility issues, and so 
 * every aspect of our reporting page should be designed
 * designed to equalify as many issues as possible.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// The tag_id URL parameter defines the tag.
$page_id = $_GET['page_id'];
if($page_id == ''){
    throw new Exception(
        'page_id is missing'
    );
}

// Let's get the various components we need to create the view.
require_once('../components/header.php');
require_once('../components/footer.php');
require_once('../components/search.php');
require_once('../components/chart_of_status.php');
require_once('../components/occurrence_table.php');

// Let's get the parameters on the page.
require_once('../helpers/get_page_url.php');
$page_url = get_page_url($page_id);

?>

<div class="d-flex flex-column flex-md-row align-items-center my-4">
    <h1>

        <?php
        // Page URL functions as a title
        echo $page_url;
        ?>

    </h1>

    <div class="ms-md-auto">
        

    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card my-2 p-4">
            <h2 class="visually-hidden">Status Occurrences Over Time</h2>

            <?php
            // Chart component.
            the_chart_of_status('pages='.$page_id);
            ?>

        </div>
    </div>
</div>

<?php

// Occurrence Table
the_occurrence_table($filters = '');