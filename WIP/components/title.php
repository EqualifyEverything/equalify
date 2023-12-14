<?php
// Shows the title
function the_title($pdo, $id, $view){

    $title = '';

    if($view == 'report'){

        // Query to fetch report title
        $stmt = $pdo->prepare("SELECT report_title FROM reports WHERE report_id = :report_id");
        $stmt->execute(['report_id' => $id]);
        $title = $stmt->fetchColumn() ?: 'Report Not Found';

    } elseif($view == 'message'){

        // Query to fetch message title
        $stmt = $pdo->prepare("SELECT message_title FROM messages WHERE message_id = :message_id");
        $stmt->execute(['message_id' => $id]);
        $title = $stmt->fetchColumn() ?: 'Message Not Found';
    }

    echo '<h1 style="max-width:800px">' . $title . '</h1>';
}