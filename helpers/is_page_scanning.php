<?php
// See if page is scanning
function is_page_scanning($page_id){
    
    // Get db connection.
    global $pdo;

    // Let's see if a scan is running for the page
    $sql = "SELECT 1 FROM queued_scans WHERE queued_scan_page_id = :page_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':page_id', $page_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row){
        return true;
    }else{
        return false;
    }

}