<?php

/**
 * Fetch the page URL by Page ID.
 * 
 * @param int $page_id The page ID.
 * @return string The page URL.
 * @throws Exception if no URL is found for the given page ID.
 */
function get_page_url($page_id) {
    
    // Include the database connection and utility functions
    require_once('../db.php');

    // Prepare the SQL query to prevent SQL injection
    $stmt = $pdo->prepare("SELECT page_url FROM pages WHERE page_id = :page_id");

    // Bind the page ID parameter
    $stmt->bindParam(':page_id', $page_id, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("No URL found for page ID: " . $page_id);
    }

    return $result['page_url'];
}

?>