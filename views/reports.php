<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the reports setting's view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/
?>

<main class="container">
    <div class="row  align-items-center">
        <div class="col">
            <h1>Reports</h1>
        </div>
        <div class="col text-end">
            <a class="btn btn-primary" href="index.php?view=report_settings">New Report</a>
        </div>
    </div>
    <div class="row row-cols-1 row-cols-md-2 g-4 my-2">

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
    // Fallback.
    endforeach; else:
    ?>

        <p class="lead">No reports exist.</p>

    <?php 
    // End Reports
    endif;
    ?>

    </div>
    
</main>