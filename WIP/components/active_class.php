<?php
/**
 * The Active Selection
 */
function the_active_class($selection){

    // 'active' class is largely dependant on view.
    if(!empty($_GET['view'])){

        // Anything will be active if the selection is also set in the view.
        if(
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