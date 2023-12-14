<?php
// Shows the body
function the_body($pdo, $id, $view){

    $body = '';

    if($view == 'message'){

        // Query to fetch message title
        $stmt = $pdo->prepare("SELECT message_body FROM messages WHERE message_id = :message_id");
        $stmt->execute(['message_id' => $id]);
        $body = $stmt->fetchColumn() ?: 'Message Not Found';
    }
    echo '<div class="card p-4  h-100">' . $body . '</div>';
}