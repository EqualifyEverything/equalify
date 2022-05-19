<?php
/**
 * The Active View
 */
function the_active_view($view){
    if(!empty($_GET['view'])){
        if($_GET['view'] == $view)
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
    if(strpos($_SERVER['REQUEST_URI'], 'success'))
        echo '<div class="alert alert-success" role="alert">Update was successful!</div>';

}

/**
 * The Page Type Badge
 */
function the_page_type_badge($page_type){
    
    // Every letter is uppercase so we have to create
    // conditions for "WordPress" or "XML" or any other
    // name that requires unique casing.
    $page_type = str_replace('_', ' ', strtoupper($page_type));
    echo '<span class="badge bg-light text-dark">'
        .$page_type.
        '<span class="visually-hidden"> Page Type</span></span>';

}

/**
 * The Integration Status Badge
 */
function the_integration_status_badge($integration_status){

    // Set badge
    // doesn't include 'planned' 'cuz the button says that.
    if($integration_status == 'Disabled'){
        $badge_class = 'bg-secondary';
        $badge_text = 'Disabled';
        echo '<span class="badge '.$badge_class.'">'.$badge_text.'<span class="visually-hidden"> Integration Status</span></span>';
    }elseif($integration_status == 'Active'){
        $badge_class = 'bg-success';
        $badge_text = 'Active';
        echo '<span class="badge '.$badge_class.'">'.$badge_text.'<span class="visually-hidden"> Integration Status</span></span>';
    }else{
        return false;
    }

}

/**
 * The Integration Activation Button
 */
function the_integration_activation_button($integration_uri, $integration_status){

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
    echo '<a href="actions/toggle_integration_status.php?uri='.$integration_uri.'&old_status='.$integration_status.'" class="btn '.$button_class.'">'.$button_text.'</a>';

}

/**
 * The Integration Settings Button
 */
function the_integration_settings_button($integration_uri, $integration_status){

    // Only show button on active inteagrations
    if($integration_status == 'Active'){
        echo '<a href="?view=integration_settings&uri='.$integration_uri.'" class="btn btn-secondary">Settings</a>';
    }else{
        return false;
    }

}

/**
 * The Scan Rows
 */
function the_scan_rows($scans){

    // Needs output buffer so HTML can be stored.
    ob_start();

        // Begin Scans
        if(count($scans) > 0 ): foreach($scans as $scan):    
        ?>

        <tr>
            <td><?php echo $scan->time;?></td>
            <td><?php echo ucwords($scan->status);?></td>
            <td>

            <?php             
            // Link to pages    
            $page_ids = unserialize($scan->pages);
            echo count($page_ids);
            ?>

            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="3">No scans found.</td>
        </tr>

        <?php 
        
        // End Scans
        endif;

    // Clean the buff.
    echo ob_get_clean();

}

/**
 * Convert Code Shortcode
 */
function covert_code_shortcode($subject){

    // Convert text between [code][/code] into styled
    // code.
    $subject = str_replace('[code]', '<pre class="bg-dark text-white p-3 mb-1"><code>', $subject);
    $subject = str_replace('[/code]', '</code></pre>', $subject);

    // [code] is converted!
    return $subject;

}