<?php
// Add initialization info
require_once('../init.php');

// Prepare the SQL statement
$stmt = $pdo->prepare("INSERT INTO reports (report_title) VALUES (:report_title)");

// Bind the parameters
$title = "Untitled Report";
$stmt->bindParam(':report_title', $title);

// Execute the statement
$stmt->execute();

// Get the last inserted ID
$report_id = $pdo->lastInsertId();

// Redirect to the desired page with the report_id
header("Location: ../index.php?view=report_settings&report_id=" . urlencode($report_id));
exit;

?>
