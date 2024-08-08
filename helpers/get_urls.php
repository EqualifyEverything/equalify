<?php
function get_urls(){
    
    global $pdo;

    // Query to fetch 
    $stmt = $pdo->prepare("SELECT url_id, url FROM urls;");
    $stmt->execute();
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$content) {
        $content = 'No URLs Found';
    }else{
        return $content;
    }
    
}
