<?php
// Components 
require_once('components/success_or_error_message.php');

// Helpers
require_once('helpers/get_title.php');

// Creates a list of pages with the percent equalfied.
function the_report_header() {
    global $report_id;
?>

<div class="pb-2 my-0 ">

    <?php
    // Add notice when there's unsaved schanges
    $cookie_name = "queue_report_" . $report_id . "_filter_change";
    if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]'):
    ?>

    <aside class="fw-semibold bg-opacity-10 text-info-emphasis bg-info-subtle border-bottom border-info" aria-label="Note on preview state" >
        <div class="container d-flex align-items-center justify-content-between py-4 ">
            Previewing Unsaved Report Settings
            <div>
                <a href="actions/delete_report_filter_cookie.php?report_id=<?php echo $report_id; ?>&redirect=report" class="btn btn-sm btn-outline-secondary">
                    Cancel Updates
                </a> 
                <a href="actions/save_report_filter_change.php?report_id=<?php echo $report_id; ?>" class="btn btn-primary btn-sm">
                    Save for Everyone
                </a>
            </div>
        </div>
    </aside>

    <?php
    // End unsaved changes notice
    endif;
    ?>
    
    <div class="container">

        <?php
        // Success or Error message
        the_success_or_error_message();
        ?>

        <div class="d-flex flex-column flex-md-row align-items-center mt-4">
            <h1 class="display-5" style="max-width:800px">

                <?php
                // Link sub report pages to main report
                if($_GET['view'] == 'report'){
                
                    // Page Title
                    echo get_title($report_id, 'report');
                    ?>

                </a>

                <?php
                // Link sub report pages to main report
                }else{
                ?>

                <a href="?view=report&report_id=<?php echo $report_id;?>" class="link-dark link-underline link-underline-opacity-0 link-underline-opacity-75-hover">

                <?php
                // Page Title
                echo get_title($report_id, 'report');
                ?>

                    <span class="visually-hidden"> (Linked to Main Report)</span>
                </a>

                <?php
                // Link sub report pages to main report
                };
                ?>


            </h1>
            <div class="ms-md-auto">
                <a class="btn btn-secondary" href="?view=report_settings&report_id=<?php echo $report_id;?>">
                    Edit Report
                </a>
            </div>
        </div>
    </div>
</div>

<?php
}