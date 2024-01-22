<?php

function get_next_scannable_property() {
    global $pdo;
    $query = "
        SELECT 
        property_id, 
        property_url 
    FROM properties
    WHERE 
        (property_archived != 1 OR property_archived IS NULL) AND
        (property_scanning != 1 OR property_scanning IS NULL) AND
        (property_scanned IS NULL OR property_scanned <= DATE_SUB(NOW(), INTERVAL 7 DAY)) AND
        NOT EXISTS (
            SELECT 1 FROM properties WHERE property_scanning = 1
        )
    ORDER BY property_scanned ASC
    LIMIT 1;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}