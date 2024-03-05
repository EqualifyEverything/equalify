<?php
// Add initialization info
require_once('../init.php');

try {
    // Check if report_title is set and not empty
    if (!isset($_POST['report_title']) || trim($_POST['report_title']) === '') {
        throw new Exception('Report title is required');
    }

    // Retrieve report_id from session
    $report_id = $_SESSION['report_id'] ?? null;
    if (!$report_id) {
        throw new Exception('Report ID is missing');
    }

    // Prepare the SQL statement
    $stmt = $pdo->prepare("UPDATE reports SET report_title = :report_title WHERE report_id = :report_id");

    // Bind the parameters
    $stmt->bindParam(':report_title', $_POST['report_title'], PDO::PARAM_STR);
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Remove session token to prevent unintended submissions.
    $_SESSION['report_id'] = '';

    // Redirect on success
    $_SESSION['success'] = "Title updated successfully.";
    header("Location: ../index.php?view=report_settings&report_id=" . urlencode($report_id));
    exit;

} catch (Exception $e) {
    // Handle any errors
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?view=report_settings&report_id=" . urlencode($report_id));

    exit;
}
?>
