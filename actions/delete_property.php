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

    // Delete related data from occurrences and its dependent tables
    $occurrencesStmt = $pdo->prepare("SELECT occurrence_id FROM occurrences WHERE occurrence_property_id = :property_id");
    $occurrencesStmt->execute([':property_id' => $property_id]);
    $occurrence_ids = $occurrencesStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($occurrence_ids) {
        // Delete from tag_relationships and updates table
        $pdo->exec("DELETE FROM tag_relationships WHERE occurrence_id IN (" . implode(',', $occurrence_ids) . ")");
        $pdo->exec("DELETE FROM updates WHERE occurrence_id IN (" . implode(',', $occurrence_ids) . ")");
        
        // Delete occurrences
        $pdo->exec("DELETE FROM occurrences WHERE occurrence_property_id = $property_id");
    }

    // Delete from queued_scans and properties
    $pdo->exec("DELETE FROM queued_scans WHERE queued_scan_property_id = $property_id");
    $pdo->exec("DELETE FROM properties WHERE property_id = $property_id");
    $pdo->exec("DELETE FROM pages WHERE page_property_id = $property_id");

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
