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
 * The Status Badge
 */
function the_status_badge($property){

    // Badge info
    if($property->status == 'archived'){
        $badge_status = 'bg-secondary';
        $badge_content = 'Archived';
    }else{

        // Alerts
        $alert_count = count(get_alerts_by_property($db, $property->id));
        if($alert_count == 0){
            $badge_status = 'bg-success';
            $badge_content = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16"><path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/><path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/></svg> Equalified';
        }else{
            $badge_status = 'bg-danger';
            $badge_content = $alert_count.' Alerts';
        };

    }
    echo '<span class="badge mb-2 '.$badge_status.'">'.$badge_content.'</span>';

}