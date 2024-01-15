<?php
// Add initialization info
require_once('../init.php');

// Include dependencies
require_once '../helpers/get_report_filters.php';

// Get report ID
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : '';

// Get last view
$last_view = isset($_GET['last_view']) ? $_GET['last_view'] : '';

// Get filters
$report_filters = get_report_filters()['as_array'];

// Update the database
$update_stmt = $pdo->prepare("UPDATE reports SET report_filters = :report_filters WHERE report_id = :report_id");
$updated_filters_json = json_encode($report_filters);
$update_stmt->bindParam(':report_filters', $updated_filters_json);
$update_stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
$update_stmt->execute();

// Remove the cookie
$cookie_name = 'queue_report_' . $report_id . '_filter_change';
setcookie($cookie_name, '',  time() + strtotime( '+30 days' ), '/');

// Redirect the user to the report page with the report ID
header("Location: ../index.php?&view=report&report_id=$report_id&success=".urlencode("Report filters saved for everyone."));
exit;

?>