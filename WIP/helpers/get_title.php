<?php
function get_title($id, $view){
    require_once('db.php');

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

    } elseif($view == 'page'){

        // Query to fetch page title
        $stmt = $pdo->prepare("SELECT page_url FROM pages WHERE page_id = :page_id");
        $stmt->execute(['page_id' => $id]);
        $title = $stmt->fetchColumn() ?: 'Page Not Found';

    } elseif($view == 'tag'){

        // Query to fetch page title
        $stmt = $pdo->prepare("SELECT tag_name FROM tags WHERE tag_id = :tag_id");
        $stmt->execute(['tag_id' => $id]);
        $title = $stmt->fetchColumn() ?: 'Page Not Found';

    }

    return $title;
}
