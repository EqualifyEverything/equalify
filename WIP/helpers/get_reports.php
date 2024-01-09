<?php
// The DB can be used by required info
function get_reports(){
    require_once('db.php');

    // Query to fetch reports
    $sql = "SELECT report_id, report_title FROM reports";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
}
