<?php
require_once('helpers/get_title.php');
function get_page_title()
{
    // Default title
    $title = "Equalify | Accessibility Issue Management";

    // Check if 'view' URL parameter is set
    if (isset($_GET['view'])) {
        $view = $_GET['view'];
        // Optionally, retrieve 'id' parameter
        $report_id = isset($_GET['report_id']) ? $_GET['report_id'] : '';
        $property_name = isset($_GET['property_id']) ? get_title($_GET['property_id'], $view) : '';
        $report_title = isset($_GET['report_id']) ? get_title($_GET['report_title'], $view) : '';
        $message_title = isset($_GET['message_id']) ? get_title($_GET['message_title'], $view) : '';
        $tag_name = isset($_GET['tag_id']) ? get_title($_GET['tag_id'], $view) : '';
        $page_url = isset($_GET['page_id']) ? get_title($_GET['page_id'], $view) : '';

        // Determine the title based on the view (and id if necessary)
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
            case 'page':
                $title = "Page | ". ($report_title ? $report_title : "") . " Report | Equalify";
                break;
            case 'tag':
                $title = "Tag Detail | Equalify". ($report_title ? $report_title : "") . " Report | Equalify";;
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
