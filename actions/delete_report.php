<?php
// Add initialization info
require_once('../init.php');

// Check if report_id is set in the session
if (!isset($_SESSION['report_id'])) {
    throw new Exception(
        'No Report ID specified.'
    );
    exit;
}

// Retrieve report_id from session
$report_id = $_SESSION['report_id'];

// Prepare the SQL statement
$stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = :report_id");

// Bind the parameters
$stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);

// Execute the statement
$stmt->execute();

// Remove session token to prevent unintended submissions.
$_SESSION['report_id'] = '';


// Redirect after successful deletion
$_SESSION['success'] = "Report deletion successful.";
header("Location: ../index.php?view=reports");
exit;

?>
