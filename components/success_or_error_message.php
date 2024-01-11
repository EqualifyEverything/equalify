<?php

// Success or Error Message
function the_success_or_error_message(){
    // Error message
    if(isset($_GET['error']))
        echo '<div class="my-4 p-3 bg-danger bg-opacity-10 border border-danger rounded mb-4 fw-semibold">'.htmlspecialchars($_GET['error']).'</div>';

    // Success message
    if(isset($_GET['success']))
        echo '<div class="my-4 p-3 bg-success bg-opacity-10 border border-success rounded mb-4 fw-semibold">'.htmlspecialchars($_GET['success']).'</div>';
}