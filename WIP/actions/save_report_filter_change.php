<?php
// Include the database connection file
require_once '../db.php';


// Hardcoded for demonstration
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : '';

$cookie_name = "unsaved_report_" . $report_id . "_filters";

// Check if the cookie exists
if (isset($_COOKIE[$cookie_name])) {
    // Decode URL-encoded cookie data and then decode JSON
    $new_filters = json_decode(urldecode($_COOKIE[$cookie_name]), true);

    // Fetch the existing filters from the database
    $stmt = $pdo->prepare("SELECT report_filters FROM reports WHERE report_id = :report_id");
    // Corrected the binding of parameter
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    $stmt->execute();

    $existing_filters = $stmt->fetch(PDO::FETCH_ASSOC)['report_filters'];
    $existing_filters_array = $existing_filters ? json_decode($existing_filters, true) : [];

    // Append new filters to existing ones
    $updated_filters = array_merge($existing_filters_array, $new_filters);

    // Update the database
    $update_stmt = $pdo->prepare("UPDATE reports SET report_filters = :report_filters WHERE report_id = :report_id");
    $updated_filters_json = json_encode($updated_filters);
    $update_stmt->bindParam(':report_filters', $updated_filters_json);
    $update_stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    $update_stmt->execute();

    // Remove the cookie
    setcookie($cookie_name, '', time() - 3600, '/');

    // Redirect the user to the report page with the report ID
    header("Location: ../index.php?view=report&report_id=$report_id");
    exit;

} else {
    throw new Exception("No filters cookie found for report ID: " . $report_id);
}



?>