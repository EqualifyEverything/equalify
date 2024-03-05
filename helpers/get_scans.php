<?php
function get_scans($results_per_page, $offset){
    global $pdo;

    if ($offset < 0) {
        $offset = 0;
    }    

    // Query to fetch scans with joins
    $sql = "
        SELECT 
            qs.queued_scan_job_id, 
            qs.queued_scan_property_id, 
            qs.queued_scan_processing,
            p.page_url, 
            prop.property_name
        FROM 
            queued_scans qs
        LEFT JOIN 
            pages p ON qs.queued_scan_page_id = p.page_id
        LEFT JOIN 
            properties prop ON qs.queued_scan_property_id = prop.property_id
        ORDER BY 
            (qs.queued_scan_processing = 1 OR qs.queued_scan_prioritized = 1) DESC,
            qs.queued_scan_job_id ASC
        LIMIT $results_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}