<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the reports setting's view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
?>

<h1 class="visually-hidden">Reports</h1>

<div id="reports_content" class="row row-cols-1 row-cols-md-3 g-4 justify-content-md-center">

<?php
// Show Scan Profiles
$reports = DataAccess::get_db_rows(
    'reports', [], get_current_page_number()
);
if( count($reports['content']) > 0 ):
    foreach($reports['content'] as $report): 
?>

    <div class="col">
        <div class="card">
            <div class="card-body">
                <h2 class="h5"><a href="index.php?view=single_report&id=<?php echo $report->id;?>"><?php echo $report->title;?></a></h2>
                <p class="text-secondary my-0"><?php echo $report->description;?></p>
            </div>
        </div>
    </div>

<?php 
// End Reports
 endforeach; endif;
?>

    <div class="col d-flex justify-content-center align-items-center">
        <a class="btn btn-primary" href="index.php?view=report_settings">
            New Report
        </a>
    </div>
</div>