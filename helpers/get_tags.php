<?php
function get_tags(){
    
    global $pdo;

    // Query to fetch 
    $stmt = $pdo->prepare("SELECT tag_id, tag FROM tags;");
    $stmt->execute();
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$content) {
        $content = 'No tags Found';
    }else{
        return $content;
    }
    
}
