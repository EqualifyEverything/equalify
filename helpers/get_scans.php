<?php
function get_scans($page = 1, $limit = 100){
    global $pdo;

    // Calculate offset
    $offset = ($page - 1) * $limit;
    $offset = max($offset, 0); // Ensure offset is not negative

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
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}