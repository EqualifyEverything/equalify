<?php
// Get helpers
require_once('helpers/get_title.php');

// Shows the title
function the_title($id, $view){
    echo '<h1 style="max-width:800px">' . get_title($id, $view) . '</h1>';
}