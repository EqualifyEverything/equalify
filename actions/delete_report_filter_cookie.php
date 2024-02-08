<?php
// Retrieve values from the URL query parameter
$report_id = $_GET['report_id'] ?? '';
$redirect = $_GET['redirect'] ?? 'report_settings'; // Return to report_settings by default
$filters_array = isset($_GET['filters']) ? $_GET['filters'] : null;

// Cookie name
$cookie_name = "queue_report_" . $report_id . "_filter_change";

// Remove the cookie by setting its expiration to a past time
if (isset($_COOKIE[$cookie_name])) {
    // Set the expiration date to one hour ago
    setcookie($cookie_name, '', time() - 3600, '/');
}

// Redirect the user to the report page with the report ID
session_start();
$_SESSION['success'] = "Report filters cleared.";
header("Location: ../index.php?view=$redirect&report_id=" . urlencode($report_id));
exit;
?>
