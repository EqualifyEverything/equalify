<?php
// The report_id URL parameter defines the page.
$report_id = $_GET['report_id'];
if($report_id == ''){
    throw new Exception(
        'report_id is missing'
    );
}

// Get helpers
require_once('helpers/get_title.php');
require_once('helpers/get_report_filters.php');

// Components
require_once('components/success_or_error_message.php');

// Handle Wrong Report Id
$report_title =  get_title($report_id, 'report');
if($report_title == 'Report Not Found'){
    echo '<p class="display-4 text-center my-4">Report not found.</p>';
    exit;
}

// Components
require_once('components/report_filter_search.php');
require_once('components/active_filters.php');

$report_filters = get_report_filters();

// Use Session to securely handle report ID
session_start();
$_SESSION['report_id'] = $report_id;

?>

<div class="container">

    <?php
    // Success or Error message
    the_success_or_error_message();
    ?>

    <h1 class="display-5 mt-4" style="max-width:800px">
        <a href="?view=report&report_id=<?php echo $report_id;?>" class="link-dark link-underline link-underline-opacity-0 link-underline-opacity-75-hover">

            <?php
            // Page Title
            echo $report_title;
            ?>

        </a>
    </h1>
    <h2 class="mb-4">Report Settings</h2>
    <div class="card my-2 p-4">
        <form class="pb-4 my-2" action="actions/save_report_title.php" method="post">
            <h3 class="mb-4">General Settings</h3>
            <div>
                <label for="reportTitle" class="form-label">Report Title</label>
                <input 
                    type="text" 
                    class="form-control <?php if(isset($_GET['error'])) echo 'is-invalid';?>" 
                    id="reportTitle" 
                    name="report_title" 
                    style="max-width: 400px" 
                    value="<?php echo get_title($report_id, 'report');?>"
                    required
                >
                
            </div>
            <button type="submit" class="btn btn-primary visually-hidden mt-3 disabled" aria-disabled="true">Update Title</button>
        </form>
        <div class="border-top py-4 my-2">
            <h3 class="mb-4">Filters</h3>
            <div class="d-flex align-items-start flex-wrap">

            <?php
            // Active filters.
            the_active_filters($report_id, $report_filters['as_array']);

            // Filter search component.
            the_report_filter_search($report_id);
            ?>

            </div>
            
            <?php
                // Unsaved changes update the state of a button
                $aria_disabled_state = true;
                $extra_classes = 'disabled visually-hidden';
                $hidden_class = 'visually-hidden';
                $disabled_class = 'disabled';
                $cookie_name = "queue_report_" . $report_id . "_filter_change";
                if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && urldecode($_COOKIE[$cookie_name]) !== '[]'){
                    $aria_disabled_state = false;
                    $disabled_class = '';    
                    $hidden_class = '';
                }
            ?>

            <div class="mt-2 p-3 bg-info bg-opacity-10 border border-info rounded <?php echo $hidden_class;?>" style="display:inline-block">
                <h4 class="visually-hidden">Filter Save Actions</h3>
                <a href="actions/save_report_filter_change.php?&report_id=<?php echo $report_id; ?>" class="btn btn-primary <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                    Save Filters for Everyone
                </a>
                <a href="?view=report&report_id=<?php echo $report_id?>" class="btn  btn-outline-primary <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                    Preview Filter Updates
                </a> 
                <a href="actions/delete_report_filter_cookie.php?report_id=<?php echo $report_id; ?>" class="btn btn-outline-secondary <?php echo $disabled_class;?>" aria-disabled="<?php echo $aria_disabled_state;?>">
                    Cancel Updates
                </a> 
            </div>
        </div>
        <div class="border-top py-4">
            <h3 class="mb-4">Danger Zone</h3>
            <p>
                <a href="actions/delete_report.php" class="btn btn-danger">Delete Report</a>
            </p>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var reportTitleInput = document.getElementById('reportTitle');
        var updateButton = document.querySelector('button[type="submit"]');

        reportTitleInput.addEventListener('keyup', function() {
            // Remove the 'disabled' and 'visually-hidden' classes from the button
            updateButton.classList.remove('disabled', 'visually-hidden');

            // Update the 'aria-disabled' attribute to 'false'
            updateButton.setAttribute('aria-disabled', 'false');
        });
    });
</script>