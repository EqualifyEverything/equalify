<?php

// Success or Error Message
function the_success_or_error_message(){

    // Error message
    if (isset($_GET['error']) || isset($_SESSION['error'])) {
        $errorMsg = isset($_GET['error']) ? $_GET['error'] : $_SESSION['error'];
        echo '<div class="my-4 p-3 bg-danger bg-opacity-10 border border-danger rounded mb-4 fw-semibold" >' . htmlspecialchars($errorMsg) . '</div>';
    }

    // Success message
    if (isset($_GET['success']) || isset($_SESSION['success'])) {
        $successMsg = isset($_GET['success']) ? $_GET['success'] : $_SESSION['success'];
        echo '<div class="my-4 p-3 bg-success bg-opacity-10 border border-success rounded mb-4 fw-semibold">' . htmlspecialchars($successMsg) . '</div>';
    }

    // Clear the session messages so they don't show again on refresh
    if (isset($_SESSION['success'])) {
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        unset($_SESSION['error']);
    }

}