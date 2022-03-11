<?php
/**
 * Find Current View
 */
function current_view(){
    if(!empty($_GET['view'])){
        return $_GET['view'];
    }
}