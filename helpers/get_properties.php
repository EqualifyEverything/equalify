<?php
// The DB can be used by required info
function get_properties(){
    global $pdo;

    // Query to fetch reports
    $sql = "SELECT property_id, property_name, property_archived, property_scanned FROM properties";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
}
