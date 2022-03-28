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
 * The Integration Status Badge
 */
function the_integration_status($integration_status){
    
    // Integration Status Variables
    if($integration_status == 'active'){
        $badge_class = 'bg-success';
        $badge_text = 'Active';
    }elseif($integration_status == 'disabled'){
        $badge_class = 'bg-secondary';
        $badge_text = 'Disabled';
    }else{
        $badge_class = 'bg-dark';
        $badge_text = 'Planned';
    }
    echo '<span class="badge '.$badge_class.'">'.$badge_text.'<span class="visually-hidden"> Integration Status</span></span>';

}

/**
 * The Type Badge
 */
function the_property_type($property_type){
    
    // Type Status
    // doesn't include 'static' to simplify the ux
    if($property_type == 'wordpress')
        echo '<span class="badge bg-light text-dark">WordPress<span class="visually-hidden"> Property Type</span></span>';

}