<?php
/**
 * Find  View
 */
function get_active_view($view){
    if(!empty($_GET['view'])){
        if($_GET['view'] == $view)
            return 'active';
    }else{
        return null;
    }
}