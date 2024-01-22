<?php
function get_property($property_id){
    global $pdo;

    // Prepare the SQL statement to fetch a specific property
    $sql = "SELECT property_id, property_name, property_archived, property_url, property_scanned, property_scanning FROM properties WHERE property_id = :property_id";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind the property_id parameter
    $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch and return the property
    return $stmt->fetch(PDO::FETCH_ASSOC);
    
}
