<?php
function get_reports(){
    global $pdo;

    // Query to fetch reports
    $sql = "SELECT report_id, report_title FROM reports";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
}
