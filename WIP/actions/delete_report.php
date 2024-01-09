<?php
// Start the session
session_start();

// Check if report_id is set in the session
if (!isset($_SESSION['report_id'])) {
    throw new Exception(
        'No Report ID specified.'
    );
    exit;
}

// Include dependencies
require_once '../db.php';

// Retrieve report_id from session
$report_id = $_SESSION['report_id'];

// Prepare the SQL statement
$stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = :report_id");

// Bind the parameters
$stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);

// Execute the statement
$stmt->execute();

// Redirect after successful deletion
header("Location: ../index.php?view=reports&success=".urlencode("Report deletion successful."));
exit;

?>
