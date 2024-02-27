<?php
function get_page_title()
{
    // Default title
    $title = "Equalify | Accessibility Issue Management";

    // Check if 'view' URL parameter is set
    if (isset($_GET['view'])) {
        $view = $_GET['view'];
        // Optionally, retrieve 'id' parameter
        $report_id = isset($_GET['report_id']) ? $_GET['report_id'] : '';

        // Determine the title based on the view (and id if necessary)
        switch ($view) {
            case 'reports':
                $title = "Reports | Equalify";
                break;
            case 'report':
                $title = "Report" . ($report_id ? " ID- $report_id" : "") . " | Equalify";
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
                // Add other cases as needed
        }
    }

    return $title;
}

function the_active_page() {
    return isset($_GET['view']) ? $_GET['view'] : 'default';
}