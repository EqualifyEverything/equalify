<?php
function get_messages(){
    
    global $pdo;

    // Query to fetch 
    $stmt = $pdo->prepare("SELECT message_id, message FROM messages;");
    $stmt->execute();
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$content) {
        $content = 'Messages Not Found';
    }else{
        return $content;
    }
    
}
