<?php
function get_scans_count(){
    global $pdo;

    // Query to get total count of scans
    $sql = "SELECT COUNT(*) FROM queued_scans";
    $stmt = $pdo->query($sql);
    return $stmt->fetchColumn();
}