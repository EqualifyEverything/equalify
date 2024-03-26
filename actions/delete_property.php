<?php
// Add initialization info
require_once('../init.php');

try {
    // Assuming $property_id is obtained securely (e.g., from session or validated POST data)
    $property_id = $_SESSION['property_id'] ?? null;

    if (!$property_id) {
        throw new Exception("Property ID is required.");
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete from urls and save url_id values
    $sqlSelectUrls = "SELECT url_id FROM urls WHERE url_property_id = ?";
    $stmtSelectUrls = $pdo->prepare($sqlSelectUrls);
    $stmtSelectUrls->execute([$property_id]);
    $urlIds = $stmtSelectUrls->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($urlIds)) {
        $inQuery = implode(',', array_fill(0, count($urlIds), '?')); // Placeholder for IN clause

        // Actual deletion of urls
        $sqlDeleteUrls = "DELETE FROM urls WHERE url_id IN ($inQuery)";
        $stmtDeleteUrls = $pdo->prepare($sqlDeleteUrls);
        $stmtDeleteUrls->execute($urlIds);

        // Delete from nodes using saved url_id values and save node_id values
        $sqlSelectNodes = "SELECT node_id FROM nodes WHERE node_url_id IN ($inQuery)";
        $stmtSelectNodes = $pdo->prepare($sqlSelectNodes);
        $stmtSelectNodes->execute($urlIds);
        $nodeIds = $stmtSelectNodes->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($nodeIds)) {
            $inQueryNodes = implode(',', array_fill(0, count($nodeIds), '?')); // Placeholder for IN clause

            // Actual deletion of nodes
            $sqlDeleteNodes = "DELETE FROM nodes WHERE node_id IN ($inQueryNodes)";
            $stmtDeleteNodes = $pdo->prepare($sqlDeleteNodes);
            $stmtDeleteNodes->execute($nodeIds);

            // Delete from node_updates using saved node_id values
            $sqlDeleteNodeUpdates = "DELETE FROM node_updates WHERE node_id IN ($inQueryNodes)";
            $stmtDeleteNodeUpdates = $pdo->prepare($sqlDeleteNodeUpdates);
            $stmtDeleteNodeUpdates->execute($nodeIds);
        }
    }

    // Delete from queued_scans and properties
    $pdo->exec("DELETE FROM queued_scans WHERE queued_scan_property_id = $property_id");
    $pdo->exec("DELETE FROM properties WHERE property_id = $property_id");

    // Process report_filters in reports table
    $reportsStmt = $pdo->query("SELECT report_id, report_filters FROM reports");
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reports as $report) {
        if(!empty($report['report_filters'])){
            $filters = json_decode($report['report_filters'], true);

            // Check if filters contain the property and remove it
            $filtersModified = false;
            foreach ($filters as $key => $filter) {
                if ($filter['filter_type'] == 'properties' && $filter['filter_id'] == $property_id) {
                    unset($filters[$key]);
                    $filtersModified = true;
                }
            }

            // Update the report if filters were modified
            if ($filtersModified) {
                $updatedFiltersJson = json_encode(array_values($filters));
                $updateStmt = $pdo->prepare("UPDATE reports SET report_filters = :filters WHERE report_id = :report_id");
                $updateStmt->execute([
                    ':filters' => $updatedFiltersJson,
                    ':report_id' => $report['report_id']
                ]);
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Remove session token to prevent unintended submissions.
    $_SESSION['property_id'] = '';

    // Success redirection or message
    $_SESSION['success'] = "Property and related data deleted.";
    header("Location: ../index.php?view=discovery");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();

    // Error redirection or message
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?view=discovery");
    exit;
}
?>
