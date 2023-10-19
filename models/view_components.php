<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Here, we set functions that are regularly used to create
 * our views.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Get Default View
 */
function get_default_view(){

    // Set the default view if no other view is selected.
    return "views/reports.php";
    
}

/**
 * The Active Selection
 */
function the_active_class($selection){

    // 'active' class is largely dependant on view.
    if(!empty($_GET['view'])){

        // Reports and presets need special treatment.
        if(
            (
                !empty($_GET['report'])
                && ($_GET['report'] == $selection)
            ) || (
                !empty($_GET['preset'])
                && ($_GET['preset'] == $selection)
            )
        ){

            // The active class is what we use to show a
            // page is selected.
            echo 'active';

        // Customizing reports also need special treatment.
        }elseif(
            !empty($_GET['meta_name'])
            && ($_GET['view'] == 'report_settings')
        ){

            echo '';

        // Anything that's not a report will be active if
        // the selection is also set in the view.
        }elseif(
            $_GET['view'] == $selection 
            && empty($_GET['report'])
            && empty($_GET['preset'])
         ){
            echo 'active';
        }

    // We need to return active for the default view.
    }elseif(empty($_GET['view']) && $selection == 'reports'){
        echo 'active';
    }else{
        return null;
    }
    
}

/**
 * The Success Message
 */
function the_success_message(){

    // Success Message
    if(strpos($_SERVER['REQUEST_URI'], 'success')){
?>

    <div class="notice notice-success" role="notice">
        Update was successful!
    </div>

<?php
    }

}

/**
 * The Integration Status Badge
 */
function the_integration_status_badge(
    $integration_status
    ){

    // Set badge doesn't include 'planned' 
    // 'cuz the button says that.
    if($integration_status == 'Disabled'){
        $badge_class = 'bg-secondary';
        $badge_text = 'Disabled';
        echo '
            <span class="badge '.$badge_class.'">'
            .$badge_text.'<span class="visually-hidden"> 
            Integration Status</span></span>';
    }elseif($integration_status == 'Active'){
        $badge_class = 'bg-success';
        $badge_text = 'Active';
        echo '<span class="badge '.$badge_class.'">'
            .$badge_text.'<span class="visually-hidden"> 
            Integration Status</span></span>';
    }else{
        return false;
    }

}

/**
 * The Integration Activation Button
 */
function the_integration_activation_button(
    $integration_uri, $integration_status
    ){

    // Set button.
    if($integration_status == 'Planned'){
        $button_class = 'btn btn-outline-secondary disabled';
        $button_text = 'Coming Soon';
    }elseif($integration_status == 'Disabled'){
        $button_class = 'btn-primary';
        $button_text = 'Activate';
    }elseif($integration_status == 'Active'){
        $button_class = 'btn btn-outline-danger';
        $button_text = 'Disable';
    }else{
        $button_class = NULL;
        $button_text = NULL;
    }
    echo '<a href="actions/toggle_integration_status.php?uri='
        .$integration_uri.'&old_status='.$integration_status
        .'" class="btn '.$button_class.'">'.$button_text.'</a>';

}

/**
 * The Integration Settings Button
 */
function the_integration_settings_button(
    $integration_uri, $integration_status
    ){

    // Only show button on active integrations
    if($integration_status == 'Active'){
        echo '<a href="index.php?view=integration_settings&uri='
            .$integration_uri.'" class="btn btn-secondary">
            Settings</a>';
    }else{
        return false;
    }

}

/**
 * Convert Code Shortcode
 */
function covert_code_shortcode($subject){

    // Convert text between [code][/code] into styled
    // code.
    $subject = str_replace(
        '[code]', '<pre class="rounded bg-secondary 
        text-white p-3 mb-1"><code>', $subject
    );
    $subject = str_replace(
        '[/code]', '</code></pre>', $subject
    );

    // [code] is converted!
    return $subject;

}

/**
 * Get Page Number
 */
function get_current_page_number(){

    // Pull page number from URL string.
    if (isset($_GET['current_page_number'])) {
        $current_page_number = $_GET['current_page_number'];
    } else {
        $current_page_number = 1;
    }

    // Return page
    return $current_page_number;

}

/**
 * The Pagination
 * Inspired by https://www.myprogrammingtutorials.com/create-pagination-with-php-and-mysql.html
 */
function the_pagination($total_pages){

    // Define page number.
    $current_page_number = get_current_page_number();

    // Define current view.
    if(!empty($_GET['view'])){
        $view_parameters = '?view='.$_GET['view'];
    }else{
        $view_parameters = '?view='.get_default_view();
    }

    // Define report.
    if(!empty($_GET['report'])){
        $report_parameters = '&report='.$_GET['report'];
    }else{
        $report_parameters = '';
    }

    // Define preset.
    if(!empty($_GET['preset'])){
        $preset_parameters = '&preset='.$_GET['preset'];
    }else{
        $preset_parameters = '';
    }


    // Set active state as function, so we don't have to keep
    // writing this condition.
    function get_active_state(
        $current_page_number, $item_number
    ){
        if($current_page_number == $item_number){ 
            return 'active'; 
        }else{
            return null;
        }
    }

    function create_pagination_element( $view_parameters, $report_parameters, $preset_parameters, $item_number ) {
        echo
        '<li class="page-item ' . get_active_state(get_current_page_number(), $item_number) . '">' .
        '<a class="page-link" href="' .
        $view_parameters.$report_parameters.$preset_parameters .
        '&current_page_number=' . $item_number . '">' . $item_number . '</a>' . '</li>';
    }

    function create_ellipses_element() {
        echo '<li class="page-item disabled"><a class="page-link" href="">...</a></li>';
    }

    // Only show pagination for more than one page
    if($total_pages > 1):

?>

<nav aria-label="Page Navigation">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php if($current_page_number <= 1){ echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if($current_page_number <= 1){ echo '#'; } else { echo ''.$view_parameters.$report_parameters.$preset_parameters.'&current_page_number='.($current_page_number - 1); } ?>">Previous</a>
        </li>
        <li class="page-item  <?php echo get_active_state($current_page_number, 1)?>">
            <a class="page-link" href="<?php echo $view_parameters.$report_parameters.$preset_parameters;?>&current_page_number=1">1</a>
        </li>

        <?php
        if ( $total_pages > 5 ) {
            if ( $current_page_number >= 1 && $current_page_number <= 3 ) {
                //$current_page_number is close to 1 (in between 1 and 3, inclusive)
                for ( $i = 2; $i <= 4; $i++ ) {
                    create_pagination_element( $view_parameters, $report_parameters, $preset_parameters,$i );
                }
                create_ellipses_element();
            } else if ( $current_page_number >= $total_pages - 2 && $current_page_number <= $total_pages ) {
                //$current_page_number is close to $total_pages (in between $total_pages - 2 and $total_pages, inclusive)
                create_ellipses_element();
                for ( $i = $total_pages - 3; $i < $total_pages; $i++ ) {
                    create_pagination_element( $view_parameters, $report_parameters, $preset_parameters,$i );
                }
            } else {
                //$current_page_number isn't close to 1 or $total_pages
                create_ellipses_element();
                for ( $i = $current_page_number - 1; $i <= $current_page_number + 1; $i++ ) {
                    create_pagination_element( $view_parameters, $report_parameters, $preset_parameters,$i );
                }
                create_ellipses_element();
            }
        } else {
            //Less than 5 pages; simpler logic
            for ($i = 2; $i <= $total_pages - 1; $i++) {
                create_pagination_element($view_parameters, $report_parameters, $preset_parameters,$i);
            }
        }
        ?>

        <li class="page-item <?php echo get_active_state($current_page_number, $total_pages)?>">
            <a class="page-link" href="<?php echo $view_parameters.$report_parameters.$preset_parameters;?>&current_page_number=<?php echo $total_pages; ?>"><?php echo $total_pages;?></a>
        </li>
        <li class="page-item <?php if($current_page_number >= $total_pages){ echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if($current_page_number >= $total_pages){ echo '#'; } else { echo ''.$view_parameters.$report_parameters.$preset_parameters.'&current_page_number='.($current_page_number + 1); } ?>">Next</a>
        </li>
    </ul>
</nav>

<?php
    // End pagination.
    endif;
}