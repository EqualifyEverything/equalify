<?php
require_once('helpers/get_title.php');

function get_page_title()
{
    global $pdo;
    // Default title
    $title = "Equalify | Accessibility Issue Management";
    $report_id = isset($_GET['report_id']) ? $_GET['report_id'] : '';


    // Check if 'view' URL parameter is set
    if (isset($_GET['view'])) {
        $view = $_GET['view'];

        // Optionally, retrieve 'id' parameter
        if (isset($_GET['report_id'])) {
            $stmt = $pdo->prepare("SELECT report_title FROM reports WHERE report_id = :report_id");
            $stmt->execute(['report_id' => $report_id]);
            $report_title = $stmt->fetchColumn() ?: 'Report Not Found';
        }
        if (isset($_GET['property_id'])) {
            $stmt = $pdo->prepare("SELECT property_name FROM properties WHERE property_id = :property_id");
            $stmt->execute(['property_id' => $_GET['property_id']]);
            $property_name = $stmt->fetchColumn() ?: 'Property Not Found';
        }
        if (isset($_GET['message_id'])) {
            $stmt = $pdo->prepare("SELECT message_title FROM messages WHERE message_id = :message_id");
            $stmt->execute(['message_id' => $_GET['message_id']]);
            $message_title = $stmt->fetchColumn() ?: 'Message Not Found';
        }
        if (isset($_GET['tag_id'])) {
            $stmt = $pdo->prepare("SELECT tag_name FROM tags WHERE tag_id = :tag_id");
            $stmt->execute(['tag_id' => $_GET['tag_id']]);
            $tag_name = $stmt->fetchColumn() ?: 'Tag Not Found';
        }
        if (isset($_GET['page_id'])) {
            $stmt = $pdo->prepare("SELECT page_url FROM pages WHERE page_id = :page_id");
            $stmt->execute(['page_id' => $_GET['page_id']]);
            $page_url = $stmt->fetchColumn() ?: 'Page Not Found';
        }
        
        switch ($view) {
            case 'property_settings':
                $title = ($property_name ? " ID- $property_name" : "") . " Property Settings | Equalify";
                break;
            case 'reports':
                $title = " All Reports | Equalify";
                break;
            case 'scans':
                $title = "Scans | Equalify";
                break;
            case 'settings':
                $title = "Settings | Equalify";
                break;
            case 'account':
                $title = "My Account | Equalify";
                break;
            case 'report':
                $title = ($report_title ? $report_title : "") . " Report | Equalify";
                break;
            case 'message':
                $title = ($message_title ? $message_title : "") . " Message Detail | " . ($report_title ? $report_title : "") . " Report | Equalify";
                break;
            case 'tag':
                $title = ($tag_name ? $tag_name : "") . " Tag Detail | Equalify " . ($report_title ? $report_title : "") . " Report | Equalify";;
                break;
            case 'page':
                $title = ($page_url ? $page_url : "") . " Page | " . ($report_title ? $report_title : "") . " Report | Equalify";
                break;
                // Add other cases as needed
        }
    }

    return $title;
}

function the_active_page()
{
    return isset($_GET['view']) ? $_GET['view'] : 'default';
}
