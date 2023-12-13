<?php
// Toggle statuses
function the_status_toggle($report_id, $report_filters) {

    // Parse $report_filters to check active statuses
    $activeStatuses = [];
    parse_str($report_filters, $parsedFilters);
    if (isset($parsedFilters['statuses'])) {
        $activeStatuses = explode(',', $parsedFilters['statuses']);
    }

    function generateStatusLink($status, $activeStatuses, $count, $report_id) {
        $allStatuses = ['equalified', 'active', 'ignored'];
        $isActive = in_array($status, $activeStatuses);
    
        if ($isActive) {
            // Link to remove the filter
            $action = "remove_filter.php";
            $query = "report_id=$report_id&filter_type=statuses&filter_value=$status&filter_id=$status";
            $class = 'active';
        } else {
            // Link to add the filter
            $action = "add_unsaved_report_filters.php";
            $query = "report_id=$report_id";
            $class = '';
            
            // Include only the clicked filter's information if other filters are active
            if (!empty($activeStatuses)) {
                $filterString = http_build_query(['filter_type' => 'statuses', 'filter_value' => $status, 'filter_id' => $status]);
                $query .= "&filters[]=" . urlencode($filterString);
            } else {
                // If no filters are active, include all other statuses in the filters
                foreach ($allStatuses as $otherStatus) {
                    if ($otherStatus !== $status) {
                        $filterString = http_build_query(['filter_type' => 'statuses', 'filter_value' => $otherStatus, 'filter_id' => $otherStatus]);
                        $query .= "&filters[]=" . urlencode($filterString);
                    }
                }
                $class = 'active';
            }
        }
    
        return "<a id='$status' class='nav-link text-white $class' href='actions/$action?$query'>
                    <span class='h1' id='{$status}_count'>$count</span><br>" . ucfirst($status) . "</span>
                </a>";
    }
    
    
?>

<div id="reports_filter" class="my-2 rounded-3 bg-secondary text-center p-2 border">
    <ul class="nav d-flex justify-content-around" aria-label="Click to toggle any of these statuses. Toggling a status will hide/show related data.">
        <li class="nav-item">
            <?php echo generateStatusLink('equalified', $activeStatuses, 0, $report_id); ?>
        </li>
        <li class="nav-item">
            <?php echo generateStatusLink('active', $activeStatuses, 0, $report_id); ?>
        </li>
        <li class="nav-item">
            <?php echo generateStatusLink('ignored', $activeStatuses, 0, $report_id); ?>
        </li>
    </ul>
</div>
<script>
    function fetchStatusCounts() {
        const xhr = new XMLHttpRequest();
        const url = 'api/?request=statuses&<?php echo $report_filters; ?>';
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const statuses = response.statuses;

                document.getElementById('equalified_count').textContent = statuses.equalified || 0;
                document.getElementById('active_count').textContent = statuses.active || 0;
                document.getElementById('ignored_count').textContent = statuses.ignored || 0;
            } else {
                console.error('Error loading status counts.');
            }
        };
        xhr.onerror = function() {
            console.error('Error on AJAX request for status counts.');
        };
        xhr.send();
    }

    // Initial fetch
    fetchStatusCounts();
</script>

<?php
}